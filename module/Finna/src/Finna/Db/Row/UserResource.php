<?php

/**
 * Row Definition for user_resource
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
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace Finna\Db\Row;

/**
 * Row Definition for user_resource
 *
 * @category VuFind
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 *
 * @property int $finna_custom_order_index
 */
class UserResource extends \VuFind\Db\Row\UserResource implements
    \Finna\Db\Entity\FinnaUserResourceEntityInterface
{
    /**
     * Custom order index setter
     *
     * @param ?int $index New due date reminder setting.
     *
     * @return static
     */
    public function setFinnaCustomOrderIndex(?int $index): static
    {
        $this->finna_custom_order_index = $index;
        return $this;
    }

    /**
     * Custom order index getter
     *
     * @return int
     */
    public function getFinnaCustomOrderIndex(): ?int
    {
        return $this->finna_custom_order_index;
    }
}
