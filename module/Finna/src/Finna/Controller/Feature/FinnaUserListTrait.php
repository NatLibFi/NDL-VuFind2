<?php

/**
 * Finna user list support trait.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2024.
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
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\Controller\Feature;

/**
 * Finna user list support trait.
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
trait FinnaUserListTrait
{
    /**
     * Append current URL to search memory so that return links on
     * record pages opened from a list point back to the list page.
     *
     * @return void
     */
    protected function rememberCurrentSearchUrl(): void
    {
        $memory  = $this->serviceLocator->get(\VuFind\Search\Memory::class);
        $listUrl = $this->getRequest()->getRequestUri();
        $memory->rememberSearch($listUrl);
    }

    /**
     * Are list tags enabled?
     *
     * @return bool
     */
    protected function listTagsEnabled(): bool
    {
        $check = $this->serviceLocator->get(\VuFind\Config\AccountCapabilities::class);
        return $check->getListTagSetting() === 'enabled';
    }

    /**
     * Create sort list.
     * If no sort option selected, set first one from the list to default.
     *
     * @param ?\VuFind\Db\Row\UserList $list List object
     *
     * @return array
     */
    protected function createSortList(?\VuFind\Db\Row\UserList $list): array
    {
        $table = $this->getTable('UserResource');

        $sortOptions = self::getFavoritesSortList();
        $sort = $_GET['sort'] ?? false;
        reset($sortOptions);
        $defaultSort = key($sortOptions);
        if (!$sort) {
            $sort = $defaultSort;
        }
        $sortList = [];

        if (empty($list) || !$table->isCustomOrderAvailable($list->id)) {
            array_shift($sortOptions);
            if ($sort == 'custom_order') {
                $sort = 'id desc';
            }
        }

        foreach ($sortOptions as $key => $value) {
            $sortList[$key] = [
                'desc' => $value,
                'selected' => $key === $sort,
                'default' => $key === $defaultSort,
            ];
        }
        return $sortList;
    }

    /**
     * Return the Favorites sort list options.
     *
     * @return array
     */
    public static function getFavoritesSortList()
    {
        return [
            'custom_order' => 'sort_custom_order',
            'id desc' => 'sort_saved',
            'id' => 'sort_saved asc',
            'title' => 'sort_title',
            'author' => 'sort_author',
            'year desc' => 'sort_year',
            'year' => 'sort_year asc',
            'format' => 'sort_format',
        ];
    }
}
