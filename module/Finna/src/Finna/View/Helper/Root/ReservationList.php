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
use VuFind\Db\Entity\UserEntityInterface;
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
    /**
     * Record driver
     *
     * @var DefaultRecord|null
     */
    protected ?DefaultRecord $driver = null;

    /**
     * Constructor
     *
     * @param ReservationListService $reservationListService Reservation list service
     * @param array                  $config                 Reservation list yaml as an array
     */
    public function __construct(
        protected ReservationListService $reservationListService,
        protected array $config = []
    ) {
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
        $recordOrganisation = $driver->getOrganisationInfoId();
        return $this->getListConfigurations($recordOrganisation, $driver);
    }

    /**
     * Get associative array of [organisation => configurated lists] where driver matches
     *
     * @param string                    $organisation Organisation name
     * @param DefaultRecord|string|null $driver       Record driver or datasource
     *
     * @return array
     */
    protected function getListConfigurations(string $organisation, DefaultRecord|string|null $driver): array
    {
        $result = [];
        if ($institutionConfig = $this->config[$organisation] ?? false) {
            $datasource = $driver instanceof DefaultRecord ? $driver->getDatasource() : $driver;
            $lists = [];
            foreach ($institutionConfig['Lists'] ?? [] as $list) {
                if (!$driver || (in_array($datasource, $list['Datasources'] ?? []) && $list['Identifier'] ?? false)) {
                    $lists[] = $list;
                }
            }
            if ($lists) {
                $result[$organisation] = $lists;
            }
        }
        return $result;
    }

    /**
     * Get list configuration defined by organisation and list identifier in ReservationList.yaml
     *
     * @param string         $organisation   Lists controlling organisation
     * @param string         $listIdentifier List identifier
     * @param ?DefaultRecord $record         Record object
     *
     * @return array
     */
    public function getListConfiguration(
        string $organisation,
        string $listIdentifier,
        ?DefaultRecord $record = null
    ): array {
        $configuration = $this->getListConfigurations($organisation, $record);
        foreach ($configuration as $organisation => $lists) {
            foreach ($lists as $list) {
                if ($listIdentifier === $list['Identifier']) {
                    return $list;
                }
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
    public function getListTranslations(string $building, array $list): array
    {
        return [
            'description' => 'list_description_' . $building . '_' . $list['Identifier'],
        ];
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
     * @param DefaultRecord|string    $recordOrId Record or id
     * @param UserEntityInterface|int $userOrId   User or user id
     * @param string                  $source     Search backend identifier
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getListsContainingRecord(
        DefaultRecord|string $recordOrId,
        UserEntityInterface|int $userOrId,
        string $source = DEFAULT_SEARCH_BACKEND
    ): array {
        return $this->reservationListService->getListsContainingRecord($recordOrId, $source, $userOrId);
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

    /**
     * Check if the driver is proper for desired list configuration
     *
     * @param DefaultRecord $driver         Driver to check
     * @param string        $institution    Key in yaml file to find lists for
     * @param string        $listIdentifier Value of list identifier in yaml to check if the driver passes
     *
     * @return bool
     */
    public function canRecordBeAddedToList(DefaultRecord $driver, string $institution, string $listIdentifier): bool
    {
        return !!$this->getListConfiguration($institution, $listIdentifier, $driver);
    }
}
