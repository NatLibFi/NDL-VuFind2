<?php

/**
 * LibraryCards view helper
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2024.
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\View\Helper\Root;

use Laminas\Cache\Storage\StorageInterface as Cache;
use VuFind\Cache\CacheTrait;
use VuFind\Db\Service\UserCardServiceInterface;

/**
 * LibraryCards view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class LibraryCards extends \VuFind\View\Helper\Root\LibraryCards
{
    use CacheTrait;

    /**
     * Constructor
     *
     * @param UserCardServiceInterface $cardService User card database service
     * @param Cache                    $cache       Object cache
     */
    public function __construct(
        protected UserCardServiceInterface $cardService,
        Cache $cache
    ) {
        $this->setCacheStorage($cache);
    }

    /**
     * Get barcode from profile
     *
     * @param string                $cardName Card's name
     * @param array                 $patron   Patron
     * @param VuFind\ILS\Connection $ils      ILS connection
     *
     * @return string
     */
    public function getProfileBarcode($cardName, $patron, $ils): string
    {
        if ($barcode = $this->getCachedData($cardName)) {
            return $barcode;
        }
        $profile = $ils->getMyProfile($patron);
        $barcode = '';
        if (!empty($profile['barcode'])) {
            if ($barcode = $profile['barcode']) {
                $this->putCachedData($cardName, $barcode);
            }
        }
        return $barcode;
    }
}
