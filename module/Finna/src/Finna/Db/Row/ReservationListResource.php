<?php

/**
 * Table Definition for finna_reservation_list_resource
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Db\Row;

use DateTime;
use Finna\Db\Entity\ReservationListEntityInterface;
use Finna\Db\Entity\ReservationListResourceEntityInterface;
use VuFind\Db\Entity\ResourceEntityInterface;

/**
 * Table Definition for finna_reservation_list_resource
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 *
 * @property int     $id
 * @property int     $user_id
 * @property int     $resource_id
 * @property ?int    $list_id
 * @property ?string $notes
 * @property string  $saved
 */
class ReservationListResource extends \VuFind\Db\Row\RowGateway implements ReservationListResourceEntityInterface
{
    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'finna_reservation_list_resource', $adapter);
    }

    /**
     * Id getter
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * Resource ID getter
     *
     * @return int
     */
    public function getResourceId(): int
    {
        return $this->resource_id;
    }

    /**
     * Resource ID setter
     *
     * @param ResourceEntityInterface $id Resource ID
     *
     * @return ReservationListResourceEntityInterface
     */
    public function setResource(ResourceEntityInterface $resource): ReservationListResourceEntityInterface
    {
        $this->resource_id = $resource->getId();
        return $this;
    }

    /**
     * Get list id
     *
     * @return int
     */
    public function getListId(): int
    {
        return $this->list_id;
    }

    /**
     * Set list id
     *
     * @param ReservationListEntityInterface $listId Id of list
     *
     * @return ReservationListResourceEntityInterface
     */
    public function setList(ReservationListEntityInterface $list): ReservationListResourceEntityInterface
    {
        $this->list_id = $list->getId();
        return $this;
    }

    /**
     * Created setter
     *
     * @param DateTime $dateTime Created date
     *
     * @return ReservationListResourceEntityInterface
     */
    public function setSaved(DateTime $dateTime): ReservationListResourceEntityInterface
    {
        $this->saved = $dateTime->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * Created getter
     *
     * @return DateTime
     */
    public function getSaved(): Datetime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $this->saved);
    }

    /**
     * Data setter
     *
     * @param string $data Data
     *
     * @return ReservationListResourceEntityInterface
     */
    public function setNotes(string $note): ReservationListResourceEntityInterface
    {
        $this->notes = $note;
        return $this;
    }

    /**
     * Data getter
     *
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }
}
