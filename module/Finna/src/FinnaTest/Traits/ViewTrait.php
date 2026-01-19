<?php

/**
 * Trait for tests involving Laminas Views.
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
 * @package  Tests
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace FinnaTest\Traits;

use Finna\View\Helper\Root\CleanHtmlFactory;
use FinnaTest\Cache\TestHarness\FilesystemOptions;
use FinnaTest\Container\MockContainer;
use Laminas\Cache\Storage\Adapter\Filesystem;
use VuFind\Cache\Manager as CacheManager;
use VuFind\Config\ConfigManagerInterface;
use VuFind\View\Helper\Root\CleanHtml;
use VuFindTest\Feature\ConfigRelatedServicesTrait;

/**
 * Trait for tests involving Laminas Views.
 *
 * @category VuFind
 * @package  Tests
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
trait ViewTrait
{
    use ConfigRelatedServicesTrait;

    /**
     * Get a CleanHtml helper
     *
     * @param array $customElements Custom elements
     *
     * @return CleanHtml
     */
    protected function getCleanHtml(array $customElements): CleanHtml
    {
        $container = new MockContainer($this);
        $container->add(
            'config',
            [
                'vufind' => [
                    'plugin_managers' => [
                        'view_customelement' => [
                            'aliases' => $customElements,
                        ],
                    ],
                ],
            ]
        );

        $configManager = $this->getMockConfigManager(['config' => []]);
        $container->add(ConfigManagerInterface::class, $configManager);

        $cache = new Filesystem(new FilesystemOptions());
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager->expects($this->any())
            ->method('getCache')
            ->willReturn($cache);
        $container->add(CacheManager::class, $cacheManager);

        $factory = new CleanHtmlFactory();
        return $factory($container, CleanHtml::class);
    }
}
