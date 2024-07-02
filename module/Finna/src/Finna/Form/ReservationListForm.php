<?php

/**
 * Configurable reservation list form.
 *
 * PHP version 8.1
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Form
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\Form;

use VuFind\Form\Handler\HandlerInterface;
use VuFind\Form\Handler\PluginManager as HandlerManager;
/**
 * Configurable reservation list form.
 *
 * @category VuFind
 * @package  Form
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class ReservationListForm extends Form
{
    /**
     * Reservation list request form id.
     *
     * @var string
     */
    public const RESERVATION_LIST_REQUEST = 'ReservationListRequest';

    /**
     * Reservation List config
     *
     * @var array
     */
    protected $reservationListConfig;

    /**
     * Reservable materials
     *
     * @var array
     */
    protected $reservableMaterials;

    /**
     * List id
     *
     * @param int
     */
    protected $listId;

    /**
     * Set reservation list config
     *
     * @param array $config Configuration
     *
     * @return void
     */
    public function setReservationListConfig($config): void
    {
        $this->reservationListConfig = $config;
    }

    /**
     * Get reservation list config for datasource
     *
     * @param string $datasource Datasource
     *
     * @return array
     */
    public function getDatasourceConfig($datasource): array
    {
        return $this->reservationListConfig[$datasource] ?? [];
    }

    /**
     * Get reservable materials
     *
     * @return array
     */
    public function getReservableMaterials(): array
    {
        return $this->reservableMaterials;
    }

    /**
     * Set reservable materials
     *
     * @param array $materials Materials
     *
     * @return void
     */
    public function setReservableMaterials($materials): void
    {
        $this->reservableMaterials = $materials;
    }

    /**
     * Get primary form handler
     *
     * @return HandlerInterface
     */
    public function getPrimaryHandler(): HandlerInterface
    {
        return $this->handlerManager->get('reservationlistemail');
    }

    /**
     * Get list id
     *
     * @return int
     */
    public function getListId(): int
    {
        return $this->listId;
    }

    /**
     * Set list id
     *
     * @param int $listId List id
     *
     * @return void
     */
    public function setListId($listId): void
    {
        $this->listId = $listId;
    }

    /**
     * Parse form configuration.
     *
     * @param string $formId  Form id
     * @param array  $config  Configuration
     * @param array  $params  Additional form parameters.
     * @param array  $prefill Prefill form with these values.
     *
     * @return array
     */
    protected function parseConfig($formId, $config, $params, $prefill)
    {
        $elements = parent::parseConfig($formId, $config, $params, $prefill);
        $elements[] = [
            'name' => 'materials',
            'type' => 'textarea',
            'label' => $this->translate('ReservationList::form_reservation_materials'),
            'required' => true,
        ];
        return $elements;
    }

    /**
     * After form has been sent to recipient
     *
     * 
     */
    public function afterSentToRecipient(): void
    {

    }
}
