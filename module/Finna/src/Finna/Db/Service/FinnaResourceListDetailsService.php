<?php

/**
 * Finna resource list details service interface
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  FinnaResourceList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaResourceListDetailsEntityInterface;
use Finna\Db\Entity\FinnaResourceListEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

/**
 * Finna resource list details service interface
 *
 * @category VuFind
 * @package  FinnaResourceList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class FinnaResourceListDetailsService extends AbstractDbService implements
    DbTableAwareInterface,
    DbServiceAwareInterface,
    FinnaResourceListDetailsServiceInterface
{
    use DbServiceAwareTrait;
    use DbTableAwareTrait;

    /**
     * Create a FinnaResourceListDetails entity object.
     *
     * @return FinnaResourceListDetailsEntityInterface
     */
    public function createEntity(): FinnaResourceListDetailsEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaResourceListDetails::class)->createRow();
    }

    /**
     * Delete setting for a list.
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List entity object or ID to delete
     *
     * @return void
     */
    public function deleteFinnaResourceListSetting(FinnaResourceListEntityInterface|int $listOrId): void
    {
        $this->getDbTable(\Finna\Db\Table\FinnaResourceListDetails::class)->delete($listOrId);
    }

    /**
     * Retrieve settings by an array of list IDs and list type.
     *
     * @param int[]   $listIds  List IDs to retrieve settings for
     * @param ?string $listType List type to retrieve settings for or null for all
     *
     * @return FinnaResourceListDetailsEntityInterface[]
     */
    public function getFinnaResourceListDetailsByListIds(
        array $listIds,
        string $listType = null
    ): array {
        $callback = function ($select) use ($listIds, $listType) {
            if ($listType) {
                $select->where->equalTo('list_type', $listType);
            }
            $select->where->in('id', $listIds);
            $select->order('institution');
        };
        return iterator_to_array(
            $this->getDbTable(\Finna\Db\Table\FinnaResourceListDetails::class)->select($callback)
        );
    }

    /**
     * Get finna resource list settings for user.
     *
     * @param UserEntityInterface|int $userOrId       User entity object or ID
     * @param string                  $listIdentifier Identifier of the list used by institution
     * @param string                  $institution    Institution name saved in details
     * @param ?string                 $listType       List type to retrieve settings for or omit for all
     *
     * @return FinnaResourceListDetailsEntityInterface[]
     */
    public function getFinnaResourceListDetailsByUser(
        UserEntityInterface|int $userOrId,
        string $listIdentifier = '',
        string $institution = '',
        string $listType = null
    ): array {
        $userId = $userOrId instanceof UserEntityInterface ? $userOrId->getId() : $userOrId;
        $callback = function ($select) use ($userId, $listIdentifier, $listType, $institution) {
            $select->where->equalTo('user_id', $userId);
            if ($listType) {
                $select->where->equalTo('list_type', $listType);
            }
            if ($institution) {
                $select->where->equalTo('institution', $institution);
            }
            if ($listIdentifier) {
                $select->where->equalTo('list_config_identifier', $listIdentifier);
            }
            $select->order('institution');
        };
        return iterator_to_array(
            $this->getDbTable(\Finna\Db\Table\FinnaResourceListDetails::class)->select($callback)
        );
    }

    /**
     * Retrieve settings by list or ID.
     *
     * @param FinnaResourceListEntityInterface|int $listOrId List entity object or ID to retrieve settings for
     *
     * @return FinnaResourceListDetailsEntityInterface|null
     */
    public function getFinnaResourceListDetailsById(
        FinnaResourceListEntityInterface|int $listOrId
    ): ?FinnaResourceListDetailsEntityInterface {
        $id = $listOrId instanceof FinnaResourceListEntityInterface ? $listOrId->getId() : $listOrId;
        return $this->getDbTable(\Finna\Db\Table\FinnaResourceListDetails::class)
            ->select(['list_id' => $id])->current();
    }
}
