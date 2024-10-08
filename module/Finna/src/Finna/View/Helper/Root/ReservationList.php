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

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\ReservationList\ReservationListService;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\ILS\Connection;
use VuFind\RecordDriver\DefaultRecord;

use function in_array;
use function is_array;

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
     * Get associative array of [organisation => configurated lists] where driver matches
     *
     * @param DefaultRecord $driver Record driver
     *
     * @return array
     */
    protected function getListConfigurations(DefaultRecord $driver): array
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
     * Get organisation information from ReservationList.yaml information section
     *
     * @param string $institution Institution to look for
     *
     * @return array
     */
    public function getInstitutionInformation(string $institution): array
    {
        return $this->config[$institution]['Information'] ?? [];
    }

    /**
     * Get list translations keys for list
     *
     * @param string       $building         Building to use in translation keys
     * @param array|string $listOrIdentifier List configuration or list id from configuration
     *
     * @return array
     */
    public function getListTranslationKeys(string $building, array|string $listOrIdentifier): array
    {
        $identifier = is_array($listOrIdentifier) ? $listOrIdentifier['Identifier'] : $listOrIdentifier;
        return [
            'title' => "list_title_{$building}_{$identifier}",
            'description' => "list_description_{$building}_{$identifier}",
        ];
    }

    /**
     * Check if the user has proper requirements to order records
     *
     * @param array $list List as configuration
     *
     * @return bool
     */
    public function checkUserRightsForList(array $list): bool
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
        $lists = $this->getListConfigurations($driver);

        // Set up the needed context in the view:
        $view = $this->getView();
        return $view->render('Helpers/reservationlist-reserve.phtml', compact('lists', 'driver'));
    }

    /**
     * Get available reservation lists for user, user must be invoked
     *
     * @return FinnaResourceListEntityInterface[]
     */
    public function getReservationListsForUser(): array
    {
        return $this->reservationListService->getReservationListsForUser($this->user);
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
     * Check if user is authorized to edit given list, use user from args to be compatible with userlist helper
     *
     * @param UserEntityInterface              $user User to check
     * @param FinnaResourceListEntityInterface $list List to check for user access
     *
     * @return bool
     */
    public function userCanEditList(UserEntityInterface $user, FinnaResourceListEntityInterface $list): bool
    {
        return $this->reservationListService->userCanEditList($user, $list);
    }
}
