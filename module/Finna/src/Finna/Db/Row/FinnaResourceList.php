<?php

/**
 * Row Definition for finna_resource_list
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
use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Laminas\Session\Container;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Row\RowGateway;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\MissingField as MissingFieldException;

/**
 * Row Definition for finna_resource_list
 *
 * @category VuFind
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $title
 * @property string $datasource
 * @property string $description
 * @property string $created
 */
class FinnaResourceList extends RowGateway implements
    \VuFind\Db\Service\DbServiceAwareInterface,
    FinnaResourceListEntityInterface
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
        parent::__construct('id', 'finna_resource_list', $adapter);
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
     * Get user id
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * Get user
     *
     * @return UserEntityInterface
     */
    public function getUser(): UserEntityInterface
    {
        return $this->getDbService(\VuFind\Db\Service\UserServiceInterface::class)->getUserById($this->user_id);
    }

    /**
     * Set user
     *
     * @param UserEntityInterface $user User entity
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaResourceListEntityInterface
    {
        $this->user_id = $user->getId();
        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title Title
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setTitle(string $title): FinnaResourceListEntityInterface
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Created setter
     *
     * @param DateTime $dateTime Created date
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setCreated(Datetime $dateTime): FinnaResourceListEntityInterface
    {
        $this->created = $dateTime->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * Created getter
     *
     * @return DateTime
     */
    public function getCreated(): Datetime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $this->created);
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param string $description Description
     *
     * @return FinnaResourceListEntityInterface
     */
    public function setDescription(string $description = ''): FinnaResourceListEntityInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Is the current user allowed to edit this list?
     *
     * @param UserEntityInterface $user Logged-in user
     *
     * @return bool
     */
    public function editAllowed($user): bool
    {
        return $user && $user->id == $this->user_id;
    }

    /**
     * Destroy the list.
     *
     * @param UserEntityInterface|bool $user  Logged-in user (false if none)
     * @param bool                     $force Should we force the delete without
     * checking permissions?
     *
     * @return int The number of rows deleted.
     */
    public function delete($user = false, $force = false): int
    {
        if (!$force && !$this->editAllowed($user)) {
            throw new ListPermissionException('list_access_denied');
        }

        // Remove user_resource and resource_tags rows:
        $finnaResourceListResource = $this->getDbTable(\Finna\Db\Table\FinnaResourceListResource::class);
        $finnaResourceListResource->destroyLinks(null, $this->user_id, $this->id);

        // Remove the list itself:
        return parent::delete();
    }

    /**
     * Saves the properties to the database.
     *
     * This performs an intelligent insert/update, and reloads the
     * properties with fresh data from the table on success.
     *
     * @return mixed The primary key value(s), as an associative array if the
     *     key is compound, or a scalar if the key is single-column.
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function save()
    {
        return parent::save();
    }

    /**
     * Remember that this list was used so that it can become the default in
     * dialog boxes.
     *
     * @return void
     */
    public function rememberLastUsed(): void
    {
        if (null !== $this->session) {
            $this->session->lastUsed = $this->id;
        }
    }

    /**
     * Given an array of item ids, remove them from this list
     *
     * @param UserEntityInterface $user   Logged-in user (false if none)
     * @param array               $ids    IDs to remove from the list
     * @param string              $source Type of resource identified by IDs
     *
     * @return void
     */
    public function removeResourcesById($user, $ids, $source = DEFAULT_SEARCH_BACKEND): void
    {
        if (!$this->editAllowed($user)) {
            throw new ListPermissionException('list_access_denied');
        }

        /**
         * Resource table
         *
         * @var \VuFind\Db\Table\Resource
         */
        $resourceTable = $this->getDbTable('Resource');
        $resources = $resourceTable->getResourcesByRecordIds($ids, $source);

        $resourceIDs = [];
        foreach ($resources as $current) {
            $resourceIDs[] = $current?->id ?? $current['id'];
        }

        /**
         * Reservation list to resource relation
         *
         * @var \Finna\Db\Table\FinnaResourceListResource
         */
        $listResourceTable = $this->getDbTable(\Finna\Db\Table\FinnaResourceListResource::class);
        $listResourceTable->destroyLinks(
            $resourceIDs,
            $this->user_id,
            $this->id
        );
    }
}
