<?php

/**
 * Row Definition for finna_resource_list_details
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
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Row;

use DateTime;
use Finna\Db\Entity\FinnaResourceListDetailsEntityInterface;
use Laminas\Session\Container;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Row\RowGateway;

/**
 * Row Definition for finna_resource_list_details
 *
 * @category VuFind
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property int    $id
 * @property string $institution
 * @property int    $list_id
 * @property string $list_config_identifier
 * @property string $list_type
 * @property string $ordered
 * @property string $pickup_date
 * @property string $connection
 * @property int    $user_id
 */
class FinnaResourceListDetails extends RowGateway implements
    \VuFind\Db\Service\DbServiceAwareInterface,
    FinnaResourceListDetailsEntityInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait;
    use \VuFind\Db\Service\DbServiceAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     * @param ?Container                  $session Session container
     */
    public function __construct($adapter, protected ?Container $session = null)
    {
        // Parents parent
        parent::__construct('id', 'finna_resource_list_details', $adapter);
    }

    /**
     * Get the ID of the list.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the institution.
     *
     * @return string
     */
    public function getInstitution(): string
    {
        return $this->institution;
    }

    /**
     * Set the institution.
     *
     * @param string $institution Institution
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setInstitution(string $institution): FinnaResourceListDetailsEntityInterface
    {
        $this->institution = $institution;
        return $this;
    }

    /**
     * Set list ordered date.
     *
     * @return mixed
     */
    public function setOrdered(): FinnaResourceListDetailsEntityInterface
    {
        $this->ordered = (new DateTime())->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * Set list pickup date.
     *
     * @param DateTime $pickup_date Pickup date
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setPickupDate(DateTime $pickup_date): FinnaResourceListDetailsEntityInterface
    {
        $this->pickup_date = $pickup_date->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * Set list connection.
     *
     * @param string $connection Connection
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setConnection(string $connection): FinnaResourceListDetailsEntityInterface
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get list connection.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Get list type.
     *
     * @return string
     */
    public function getListType(): string
    {
        return $this->list_type;
    }

    /**
     * Set list type.
     *
     * @param string $listType List type
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setListType(string $listType): FinnaResourceListDetailsEntityInterface
    {
        $this->list_type = $listType;
        return $this;
    }

    /**
     * Get list ordered date.
     *
     * @return ?DateTime
     */
    public function getOrdered(): ?DateTime
    {
        return $this->ordered ? DateTime::createFromFormat('Y-m-d H:i:s', $this->ordered) : null;
    }

    /**
     * Get list pickup date.
     *
     * @return ?DateTime
     */
    public function getPickupDate(): ?DateTime
    {
        return $this->pickup_date ? DateTime::createFromFormat('Y-m-d H:i:s', $this->pickup_date) : null;
    }

    /**
     * Get the list ID.
     *
     * @return int
     */
    public function getListId(): int
    {
        return $this->list_id;
    }

    /**
     * Get the list configuration identifier.
     *
     * @return string
     */
    public function getListConfigIdentifier(): string
    {
        return $this->list_config_identifier;
    }

    /**
     * Set the list ID.
     *
     * @param int $listId List ID
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setListId(int $listId): FinnaResourceListDetailsEntityInterface
    {
        $this->list_id = $listId;
        return $this;
    }

    /**
     * Set the list configuration identifier.
     *
     * @param string $listConfigIdentifier List configuration identifier
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setListConfigIdentifier(string $listConfigIdentifier): FinnaResourceListDetailsEntityInterface
    {
        $this->list_config_identifier = $listConfigIdentifier;
        return $this;
    }

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * Set the user ID.
     *
     * @param int $userId User ID
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setUserId(int $userId): FinnaResourceListDetailsEntityInterface
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * Set user id from user entity.
     *
     * @param UserEntityInterface $user User entity
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaResourceListDetailsEntityInterface
    {
        $this->user_id = $user->getId();
        return $this;
    }
}
