<?php

/**
 * Reservation list view helper
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\View\Helper\Root;

use Finna\Db\Entity\FinnaResourceListDetailsEntityInterface;
use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\ReservationList\ReservationListService;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\ILS\Connection;
use VuFind\RecordDriver\DefaultRecord;

use function in_array;

/**
 * Reservation list view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ReservationList extends \Laminas\View\Helper\AbstractHelper
{
    use DbServiceAwareTrait;

    /**
     * Record driver
     *
     * @var DefaultRecord|null
     */
    protected ?DefaultRecord $driver = null;

    /**
     * User logged in or null
     *
     * @var UserEntityInterface|null
     */
    protected ?UserEntityInterface $user;

    /**
     * Constructor
     *
     * @param ReservationListService $reservationListService Reservation list service
     * @param ILSAuthenticator       $ilsAuthenticator       Authenticator to ILS
     * @param Connection             $catalog                Current connection catalog
     * @param array                  $config                 Reservation list yaml as an array
     */
    public function __construct(
        protected ReservationListService $reservationListService,
        protected ILSAuthenticator $ilsAuthenticator,
        protected Connection $catalog,
        protected array $config = []
    ) {
    }

    /**
     * Invoke
     *
     * @param ?UserEntityInterface $user User currently logged in or null
     *
     * @return self
     */
    public function __invoke(?UserEntityInterface $user = null): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get an array of configurations where record meets criteria
     *
     * @param DefaultRecord $driver Record driver to find lists for
     *
     * @return array
     */
    protected function getListsAvailableForRecord(DefaultRecord $driver): array
    {
        return $this->getListConfigurations($driver);
    }

    /**
     * Get associative array of [organisation => configurated lists] where driver matches
     *
     * @param DefaultRecord $driver Record driver or datasource
     *
     * @return array
     */
    protected function getListConfigurations(DefaultRecord|null $driver): array
    {
        $datasource = $driver->tryMethod('getDatasource', [], '');
        if (!$datasource) {
            return [];
        }
        $result = [];
        foreach ($this->config as $institution => $settings) {
            $current = [$institution => []];
            if ($lists = $settings['Lists'] ?? []) {
                foreach ($lists as $list) {
                    if (in_array($datasource, $list['Datasources'] ?? []) && $this->checkUserRightsForList($list)) {
                        $current[$institution][] = $list;
                        continue;
                    }
                }
            }
            if ($current[$institution]) {
                $result = array_merge($result, $current);
            }
        }
        return $result;
    }

    /**
     * Get list configuration defined by organisation and list identifier in ReservationList.yaml
     *
     * @param string $organisation   Lists controlling organisation
     * @param string $listIdentifier List identifier
     *
     * @return array
     */
    public function getListConfiguration(
        string $organisation,
        string $listIdentifier
    ): array {
        foreach ($this->config[$organisation]['Lists'] ?? [] as $list) {
            if (($list['Identifier'] ?? false) === $listIdentifier) {
                return $list;
            }
        }
        return [];
    }

    /**
     * Get list translations keys for list
     *
     * @param string $building Building to use in translation keys
     * @param array  $list     List configuration
     *
     * @return array
     */
    public function getListTranslationKeys(string $building, array $list): array
    {
        $formed = '_' . $building . '_' . $list['Identifier'];
        $keysAndValues = [
            'title' => 'list_title',
            'description' => 'list_description',
        ];
        return array_map(
            fn ($value) => $value . $formed,
            $keysAndValues
        );
    }

    /**
     * Check if the user has proper requirements to order records
     *
     * @param array $list Lists as configurations
     *
     * @return bool
     */
    protected function checkUserRightsForList(array $list): bool
    {
        $sources = $list['LibraryCardSources'] ?? false;
        if (!$sources) {
            return true;
        }
        $patron = $this->ilsAuthenticator->storedCatalogLogin();
        if (!$patron) {
            return false;
        }
        return in_array($patron['source'], $sources);
    }

    /**
     * Display buttons which routes the request to proper list procedures
     * Checks if the list should be displayed for logged-in only users.
     *
     * @param DefaultRecord $driver Driver to use for checking available lists
     *
     * @return string
     */
    public function renderReserveTemplate(DefaultRecord $driver): string
    {
        // Collect lists where we could potentially save this:
        $lists = $this->getListsAvailableForRecord($driver);

        // Set up the needed context in the view:
        $view = $this->getView();
        return $view->render('Helpers/reservationlist-reserve.phtml', compact('lists', 'driver'));
    }

    /**
     * Get available reservation lists for user
     *
     * @param UserEntityInterface|int $userOrId User entity or user id
     *
     * @return array An array containing arrays:
     *               [
     *                  'list_entity' => FinnaResourceListEntityInterface,
     *                  'details_entity' => FinnaResourceListDetailsEntityInterface
     *               ]
     */
    public function getReservationListsForUser(UserEntityInterface|int $userOrId): array
    {
        return $this->reservationListService->getReservationListsForUser($userOrId);
    }

    /**
     * Get lists containing record
     *
     * @param DefaultRecord $record Record
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getListsContainingRecord(
        DefaultRecord $record,
    ): array {
        if (!$this->user) {
            return [];
        }
        return $this->reservationListService->getListsContainingRecord(
            $record,
            $record->getSourceIdentifier(),
            $this->user
        );
    }

    /**
     * Get list details
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List id
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function getListDetails(
        FinnaResourceListEntityInterface|int $listOrId
    ): FinnaResourceListDetailsEntityInterface {
        return $this->reservationListService->getListDetails($listOrId);
    }
}
