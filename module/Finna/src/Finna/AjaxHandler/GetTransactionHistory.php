<?php

/**
 * GetFeed AJAX handler
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2023.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use Exception;
use Laminas\Mvc\Controller\Plugin\Params;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\ILS\Connection;
use VuFind\ILS\PaginationHelper;
use VuFind\Session\Settings as SessionSettings;

/**
 * GetFeed AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetTransactionHistory extends \VuFind\AjaxHandler\AbstractIlsAndUserAction
{
    protected $exportFormats = [
        'xlsx' => [
            'mediaType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'writer' => Xlsx::class,
        ],
        'ods' => [
            'mediaType' => 'application/vnd.oasis.opendocument.spreadsheet',
            'writer' => Ods::class,
        ],
        'csv' => [
            'mediaType' => 'text/csv',
            'writer' => Csv::class,
        ],
    ];

    /**
     * Constructor
     *
     * @param SessionSettings       $ss               Session settings
     * @param Connection            $ils              ILS connection
     * @param ILSAuthenticator      $ilsAuthenticator ILS authenticator
     * @param ?UserEntityInterface  $user             Logged in user (or null)
     * @param \VuFind\Record\Loader $recordLoader     Record loader
     * @param int                   $batchLimit       Config specified default batch limit
     * @param int                   $defaultPageSize  Default page size set in config.ini
     */
    public function __construct(
        SessionSettings $ss,
        Connection $ils,
        ILSAuthenticator $ilsAuthenticator,
        ?UserEntityInterface $user,
        protected \VuFind\Record\Loader $recordLoader,
        protected int $batchLimit = 1000,
        protected int $defaultPageSize = 50
    ) {
        parent::__construct($ss, $ils, $ilsAuthenticator, $user);
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $patron = $this->ilsAuthenticator->storedCatalogLogin();
        if (!$patron || !$this->user) {
            return $this->formatResponse(
                $this->translate('You must be logged in first'),
                self::STATUS_HTTP_NEED_AUTH
            );
        }

        $requestType = $params->fromQuery('type', 'status');
        // Check function config
        $functionConfig = $this->ils->checkFunction(
            'getMyTransactionHistory',
            $patron
        );
        if (false === $functionConfig) {
            return $this->formatResponse(
                $this->translate('ils_action_unavailable'),
                self::STATUS_HTTP_UNAVAILABLE
            );
        }
        $paginationHelper = new PaginationHelper();
        $pageOptions = $paginationHelper->getOptions(
            1,
            null,
            $this->defaultPageSize,
            $functionConfig
        );

        // Get checked out item details:
        $result = $this->ils->getMyTransactionHistory($patron, $pageOptions['ilsParams']);
        if (!($result['success'] ?? true)) {
            return $this->formatResponse(
                $this->translate('An error has occurred'),
                self::STATUS_HTTP_ERROR
            );
        }
        if ('status' === $requestType) {
            // Get amount of items in a single page
            return $this->formatResponse(
                [
                    'parts' => ceil(($result['count'] ?? 1) / 1000),
                ]
            );
        }
        if ('file' === $requestType) {
            $paginator = $paginationHelper->getPaginator(
                $pageOptions,
                $result['count'],
                $result['transactions']
            );
            // Get requested history part as a file to be downloaded
            $part = $params->fromQuery('part', 1);
            $fileFormat = $params->fromQuery('format', 'csv');
            $pageLimit = $paginator ? $paginator->getItemCountPerPage() : 50;
            $pagesCount = $paginator ? $paginator->count() : 1;
            return $this->getHistoryAsFile($patron, $part, $pageLimit, $pagesCount, $fileFormat);
        }
        return $this->formatResponse(
            $this->translate('An error has occurred'),
            self::STATUS_HTTP_ERROR
        );
    }

    /**
     * Create a file for transaction history
     *
     * @param array  $patron     Currently logged in users patron
     * @param int    $part       Part of the transaction history to download
     * @param int    $limit      Limit for how many transactions one fetch from ils fetches
     * @param int    $pagesCount Total amount of pages the user has in history
     * @param string $fileFormat Format of the file to generate
     *
     * @return array [fileName => name of the file, mediaType => media type, filePointer => pointer for the resource]
     */
    private function getHistoryAsFile(
        array $patron,
        int $part = 1,
        int $limit = 50,
        int $pagesCount = 1,
        string $fileFormat = 'csv'
    ): array {
        // Calculate how many times required to fetch from ILS to achieve the $batchLimit
        $pagesToFetch = 1;
        $firstPageToFetch = 1;
        $lastPageToFetch = 1;
        if ($pagesCount > 1) {
            $pagesToFetch = ceil($this->batchLimit / $limit);
            $firstPageToFetch += ($pagesToFetch * ($part - 1));
            $lastPageToFetch += min(($pagesToFetch * $part) - 1, $pagesCount);
        }
        $tmp = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');

        $transactions = [];
        for ($i = $firstPageToFetch; $i <= $lastPageToFetch; $i++) {
            $result = $this->ils->getMyTransactionHistory($patron, ['page' => $i, 'limit' => $limit]);
            // Break if no transactions found
            if (empty($result['transactions'])) {
                break;
            }
            $transactions = [...$transactions, ...$result['transactions']];
        }
        $ids = [];
        foreach ($transactions as $current) {
            $id = $current['id'] ?? '';
            $source = $current['source'] ?? DEFAULT_SEARCH_BACKEND;
            $ids[] = compact('id', 'source');
        }
        $records = $this->recordLoader->loadBatch($ids, true);

        $header = [
            $this->translate('Title'),
            $this->translate('Format'),
            $this->translate('Author'),
            $this->translate('Publication Year'),
            $this->translate('Institution'),
            $this->translate('Borrowing Location'),
            $this->translate('Checkout Date'),
            $this->translate('Return Date'),
            $this->translate('Due Date'),
        ];

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray($header);

        if ('xlsx' === $fileFormat) {
            Cell::setValueBinder(new AdvancedValueBinder());
        }

        foreach ($transactions as $i => $current) {
            $driver = $records[$i];
            $format = $driver->getFormats();
            $format = end($format);
            $author = $driver->tryMethod('getNonPresenterAuthors');

            $loan = [];
            $loan[] = $current['title'] ?? $driver->getTitle() ?? '';
            $loan[] = $this->translate($format);
            $loan[] = $author[0]['name'] ?? '';
            $loan[] = $current['publication_year'] ?? '';
            $loan[] = empty($current['institution_name'])
                ? ''
                : $this->translateWithPrefix('location_', $current['institution_name']);
            $loan[] = empty($current['borrowingLocation'])
                ? ''
                : $this->translateWithPrefix('location_', $current['borrowingLocation']);
            $loan[] = $current['checkoutDate'] ?? '';
            $loan[] = $current['returnDate'] ?? '';
            $loan[] = $current['dueDate'] ?? '';

            $nextRow = $worksheet->getHighestRow() + 1;
            $worksheet->fromArray($loan, null, 'A' . (string)$nextRow);
        }
        if ('xlsx' === $fileFormat) {
            $worksheet->getStyle('G2:I' . $worksheet->getHighestRow())
                ->getNumberFormat()
                ->setFormatCode('dd.mm.yyyy');
            foreach (['G', 'H', 'I'] as $col) {
                $worksheet->getColumnDimension($col)->setAutoSize(true);
            }
        }
        $writer = new $this->exportFormats[$fileFormat]['writer']($spreadsheet);
        $writer->save($tmp);
        $fileName = implode('-', ['finna-loan-history-pages', $firstPageToFetch, $lastPageToFetch]);
        $fileName .= ".$fileFormat";

        rewind($tmp);

        return $this->formatResponse([
            'fileName' => $fileName,
            'mediaType' => $this->exportFormats[$fileFormat]['mediaType'],
            'filePointer' => $tmp,
        ], 200);
    }
}
