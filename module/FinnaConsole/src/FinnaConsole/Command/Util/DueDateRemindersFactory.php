<?php

/**
 * Factory for the "due date reminders" task.
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

use Finna\Db\Service\FinnaDueDateReminderServiceInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Crypt\SecretCalculator;
use VuFind\Db\Service\UserCardServiceInterface;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\ILS\Connection;
use VuFind\Mailer\Mailer;
use VuFind\Record\Loader;

/**
 * Factory for the "due date reminders" task.
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class DueDateRemindersFactory implements FactoryInterface
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
        $configReader = $container->get(\VuFind\Config\PluginManager::class);

        // We need to initialize the theme so that the view renderer works:
        $mainConfig = $configReader->get('config');
        $theme = new \VuFindTheme\Initializer($mainConfig->Site, $container);
        $theme->init();

        $dbServiceManager = $container->get(\VuFind\Db\Service\PluginManager::class);
        return new $requestedName(
            $dbServiceManager->get(UserServiceInterface::class),
            $dbServiceManager->get(UserCardServiceInterface::class),
            $dbServiceManager->get(FinnaDueDateReminderServiceInterface::class),
            $container->get(Connection::class),
            $container->get(ILSAuthenticator::class),
            $configReader->get('config'),
            $configReader->get('datasources'),
            $container->get('ViewRenderer'),
            $container->get(Loader::class),
            $container->get(Mailer::class),
            $container->get(Translator::class),
            $container->get(SecretCalculator::class),
            ...($options ?? [])
        );
    }
}
