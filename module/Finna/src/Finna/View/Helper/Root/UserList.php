<?php

/**
 * List view helper.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026
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
 * @package  View_Helpers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\View\Helper\Root;

use VuFind\Db\Entity\UserEntityInterface;

/**
 * List view helper.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class UserList extends \VuFind\View\Helper\Root\UserList
{
    /**
     * Get lists with counts for the provided user.
     *
     * @param UserEntityInterface $user  User owning lists
     * @param string              $order List order
     *
     * @return array
     */
    public function getUserListsAndCountsByUser(UserEntityInterface $user, string $order = ''): array
    {
        return $this->userListService->getUserListsAndCountsByUser($user, '', $order);
    }

    /**
     * Is custom order available for lists.
     *
     * @param UserEntityInterface $user User owning lists
     *
     * @return bool
     */
    public function customOrderAvailableForLists($user): bool
    {
        return $this->userListService->isCustomOrderAvailableForLists($user);
    }
}
