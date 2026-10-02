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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\Controller\Feature;

use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Entity\UserListEntityInterface;

use function in_array;

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
     * @param ?UserListEntityInterface $list List object
     *
     * @return array
     */
    protected function createSortList(?UserListEntityInterface $list): array
    {
        $userListService = $this->getDbService(\VuFind\Db\Service\UserListService::class);

        $sortOptions = self::getFavoritesSortList();
        $sort = $_GET['sort'] ?? false;
        reset($sortOptions);
        $defaultSort = key($sortOptions);
        if (!$sort) {
            $sort = $defaultSort;
        }
        $sortList = [];

        if (null === $list || !$userListService->isCustomOrderAvailable($list)) {
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
            'year' => 'sort_year_asc',
            'format' => 'sort_format',
        ];
    }

    /**
     * Create sort list for all favorite lists.
     *
     * @param ?UserEntityInterface $user User object
     *
     * @return array
     */
    protected function createSortListForAllLists(?UserEntityInterface $user): array
    {
        $sortOptions = self::getAllListsSortList();
        $sort = $_GET['allListsSort'] ?? '';
        if (!in_array($sort, array_keys($sortOptions))) {
            $sort = 'id';
        }
        $sortList = [];

        foreach ($sortOptions as $key => $value) {
            $sortList['sortList'][$key] = [
                'desc' => $value,
                'selected' => $key === $sort,
            ];
        }
        $sortList['active'] = $sort;
        return $sortList;
    }

    /**
     * Return sort list for all favorite lists.
     *
     * @return array
     */
    public static function getAllListsSortList(): array
    {
        return [
            'custom_order' => 'sort_custom_order',
            'created asc' => 'sort_created_asc',
            'created desc' => 'sort_created_desc',
            'title' => 'sort_title',
        ];
    }
}
