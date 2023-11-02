<?php

/**
 * Sample placement data adapter implementation.
 *
 * PHP version 8
 *
 * @category Wayfinder
 * @package  Wayfinder
 * @author   Inlead <support@inlead.dk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://inlead.dk
 */

namespace Finna\Wayfinder\Adapter;

use Finna\Wayfinder\DTO\WayfinderPlacement;

/**
 * Sample placement data adapter implementation.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @category Wayfinder
 * @package  Wayfinder
 * @author   Inlead <support@inlead.dk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://inlead.dk
 */
class SampleAdapter implements LocationAdapterInterface
{
    /**
     * Gets the placement location DTO.
     *
     * @param array $data Placement payload.
     *
     * @return WayfinderPlacement Marker DTO.
     */
    public function getLocation(array $data): WayfinderPlacement
    {
        return (new WayfinderPlacement())
            ->setBranch($data['source'] ?? '')
            ->setDepartment($data['branch'] ?? '')
            ->setLocation($data['department'] ?? '')
            ->setDk5($data['callnumber'] ?? '');
    }
}
