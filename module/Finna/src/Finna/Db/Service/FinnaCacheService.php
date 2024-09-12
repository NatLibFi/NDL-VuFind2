<?php

/**
 * Database service for Finna cache.
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
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use DateTime;
use Finna\Db\Entity\FinnaCacheEntityInterface;
use VuFind\Db\Service\AbstractDbService;
use VuFind\Db\Service\Feature\DeleteExpiredInterface;
use VuFind\Db\Table\DbTableAwareInterface;
use VuFind\Db\Table\DbTableAwareTrait;

/**
 * Database service for Finna cache.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class FinnaCacheService extends AbstractDbService implements
    DbTableAwareInterface,
    FinnaCacheServiceInterface,
    DeleteExpiredInterface
{
    use DbTableAwareTrait;

    /**
     * Create a FinnaCache entity object.
     *
     * @return FinnaCacheEntityInterface
     */
    public function createEntity(): FinnaCacheEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaCache::class)->createRow();
    }

    /**
     * Delete an entity.
     *
     * @param FinnaCacheEntityInterface $entity Entity
     *
     * @return void
     */
    public function deleteCacheEntry(FinnaCacheEntityInterface $entity): void
    {
        if ($id = $entity->getId()) {
            $this->getDbTable(\Finna\Db\Table\FinnaCache::class)->delete(compact('id'));
        }
    }

    /**
     * Get cache item from database by id.
     *
     * @param string $id Item id
     *
     * @return ?FinnaCacheEntityInterface
     */
    public function getByResourceId(string $id): ?FinnaCacheEntityInterface
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaCache::class)->select(['resource_id' => $id])->current();
    }

    /**
     * Delete expired records. Allows setting a limit so that rows can be deleted in small batches.
     *
     * @param DateTime $dateLimit Date threshold of an "expired" record.
     * @param ?int     $limit     Maximum number of rows to delete or null for no limit.
     *
     * @return int Number of rows deleted
     */
    public function deleteExpired(DateTime $dateLimit, ?int $limit = null): int
    {
        return $this->getDbTable(\Finna\Db\Table\FinnaCache::class)
            ->deleteExpired($dateLimit->format('Y-m-d H:i:s'), $limit);
    }
}
