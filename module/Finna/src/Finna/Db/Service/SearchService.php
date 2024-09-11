<?php

/**
 * Database service for search.
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

use VuFind\Db\Entity\SearchEntityInterface;

/**
 * Database service for search.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class SearchService extends \VuFind\Db\Service\SearchService implements
    FinnaSearchServiceInterface
{
    /**
     * Get distinct notification base URLs with scheduled alerts.
     *
     * @return array URLs
     */
    public function getScheduledNotificationBaseUrls(): array
    {
        $table = $this->getDbTable('search');
        $sql
            = "SELECT distinct notification_base_url as url FROM {$table->getTable()}"
            . " WHERE notification_base_url != '' AND notification_frequency != 0;";

        $result = $table->getAdapter()->query(
            $sql,
            \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
        $urls = [];
        foreach ($result as $res) {
            $urls[] = $res['url'];
        }
        return $urls;
    }

    /**
     * Get scheduled searches by notification base URL.
     *
     * @param string $notificationBaseUrl Notification base URL
     *
     * @return SearchEntityInterface[]
     */
    public function getScheduledSearchesByBaseUrl(string $notificationBaseUrl): array
    {
        $callback = function ($select) use ($notificationBaseUrl) {
            $select->where->equalTo('saved', 1);
            $select->where->greaterThan('notification_frequency', 0);
            $select->where->equalTo('notification_base_url', $notificationBaseUrl);
            $select->order('user_id');
        };
        return iterator_to_array($this->getDbTable('search')->select($callback));
    }
}
