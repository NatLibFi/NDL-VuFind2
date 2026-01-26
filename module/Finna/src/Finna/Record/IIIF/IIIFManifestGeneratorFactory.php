<?php

/**
 * IIIF manifest generator factory
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
 * @package  Service
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Record\IIIF;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * IIIF manifest generator factory
 *
 * @category VuFind
 * @package  Service
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class IIIFManifestGeneratorFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param mixed              $requestedName Service being created
     * @param mixed              $options       Extra options (optional)
     *
     * @throws \Exception
     *
     * @return IIIFManifestGenerator
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): IIIFManifestGenerator {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        $viewRenderer = $container->get('ViewRenderer');
        $generator = new IIIFManifestGenerator(
            $viewRenderer->plugin('url'),
            $viewRenderer->plugin('serverUrl'),
            $viewRenderer->plugin('recordLinker'),
        );
        return $generator;
    }
}
