<?php

/**
 * Factory for online payment monitor.
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
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace FinnaConsole\Command\Util;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;
use VuFind\Auth\ILSAuthenticator;

/**
 * Factory for online payment monitor.
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class OnlinePaymentMonitorFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        // We need to initialize the theme so that the view renderer works:
        $mainConfig = $container->get(\VuFind\Config\PluginManager::class)
            ->get('config');
        $theme = new \VuFindTheme\Initializer($mainConfig->Site, $container);
        $theme->init();

        $dbServiceManager = $container->get(\VuFind\Db\Service\PluginManager::class);

        return new $requestedName(
            $container->get(\VuFind\ILS\Connection::class),
            $container->get(ILSAuthenticator::class),
            $dbServiceManager->get(\Finna\Db\Service\FinnaTransactionServiceInterface::class),
            $dbServiceManager->get(\VuFind\Db\Service\UserServiceInterface::class),
            $dbServiceManager->get(\VuFind\Db\Service\UserCardServiceInterface::class),
            $container->get(\VuFind\Config\PluginManager::class)->get('datasources'),
            $container->get('ViewRenderer'),
            $container->get(\VuFind\Mailer\Mailer::class),
            $dbServiceManager->get(\Finna\Db\Service\FinnaTransactionEventLogServiceInterface::class),
            ...($options ?? [])
        );
    }
}
