<?php

/**
 * Database service for resources.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use VuFind\Db\Entity\ResourceEntityInterface;

/**
 * Database service for resources.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class ResourceService extends \VuFind\Db\Service\ResourceService implements ResourceServiceInterface
{
    /**
     * Get a batch of entities.
     *
     * @param ?int $lastId    ID of last retrieved entity, or null to start from beginning
     * @param int  $batchSize Batch size
     *
     * @return ResourceEntityInterface[]
     */
    public function getEntityBatch(?int $lastId, int $batchSize): array
    {
        $dql = 'SELECT r FROM ' . ResourceEntityInterface::class . ' r';
        $params = [];
        if (null !== $lastId) {
            $dql .= ' WHERE r.id > :lastId';
            $params['lastId'] = $lastId;
        }
        $dql .= ' ORDER BY r.id';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameters($params);
        $query->setMaxResults($batchSize);
        return $query->getResult();
    }

    /**
     * Apply a sort parameter to a query on the resource table. Returns an
     * array with two keys: 'orderByClause' (the actual ORDER BY) and
     * 'extraSelect' (extra values to add to SELECT, if necessary).
     *
     * @param string $sort  Field to use for sorting (may include
     *                      'desc' qualifier)
     * @param string $alias Alias to the resource table (defaults to 'r')
     *
     * @return array
     */
    protected function getResourceOrderByClause(string $sort, string $alias = 'r'): array
    {
        if ('custom_order' === $sort) {
            $orderByClause = ' ORDER BY custom_order ASC';
            $extraSelect = 'ur.finnaCustomOrderIndex AS HIDDEN custom_order';
            return compact('orderByClause', 'extraSelect');
        } elseif ('id desc' === $sort || 'id asc' === $sort) {
            $orderByClause = " ORDER BY ur.$sort";
            $extraSelect = '';
            return compact('orderByClause', 'extraSelect');
        }
        return parent::getResourceOrderByClause($sort, $alias);
    }
}
