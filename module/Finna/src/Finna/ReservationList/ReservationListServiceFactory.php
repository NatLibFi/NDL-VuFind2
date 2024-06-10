<?php

/**
 * Reservation list service factory.
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
 * @package  Record
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\ReservationList;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Reservation list service factory.
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ReservationListServiceFactory implements FactoryInterface
{
    /**
     * Create a ReservationListService object.
     *
     * @param ContainerInterface $sm      Service manager
     * @param string             $name    Service being created
     * @param array              $options Extra options (optional)
     *
     * @return ReservationListService
     */
    public function __invoke(ContainerInterface $sm, $name, array $options = null)
    {
        $tableManager = $sm->get(\VuFind\Db\Table\PluginManager::class);
        return new ReservationListService(
            $tableManager->get(\Finna\Db\Table\ReservationList::class),
            $tableManager->get('resource'),
            $tableManager->get(\VuFind\Db\Table\UserResource::class),
            $sm->get(\VuFind\Record\Cache::class)
        );
    }
}
