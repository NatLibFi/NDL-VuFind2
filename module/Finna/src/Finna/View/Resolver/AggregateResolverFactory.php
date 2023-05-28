<?php

/**
 * Finna aggregate resolver factory.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Resolvers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\View\Resolver;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Resolver as ViewResolver;
use Laminas\View\Resolver\ResolverInterface;
use Psr\Container\ContainerInterface;

/**
 * Finna aggregate resolver factory.
 *
 * @category VuFind
 * @package  Resolvers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class AggregateResolverFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Container
     * @param string             $requestedName Requested name
     * @param null|array         $options       Options
     *
     * @return AggregateResolver
     * @throws ServiceNotFoundException If unable to resolve the service.
     * @throws ServiceNotCreatedException If an exception is raised when
     *     creating a service.
     * @throws ContainerException If any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        $resolver = new $requestedName();

        /* @var $mapResolver ResolverInterface */
        $mapResolver             = $container->get('ViewTemplateMapResolver');
        /* @var $pathResolver ResolverInterface */
        $pathResolver            = $container->get('ViewTemplatePathStack');
        /* @var $prefixPathStackResolver ResolverInterface */
        $prefixPathStackResolver = $container->get('ViewPrefixPathStackResolver');

        $resolver
            ->attach($mapResolver)
            ->attach($pathResolver)
            ->attach($prefixPathStackResolver)
            ->attach(new ViewResolver\RelativeFallbackResolver($mapResolver))
            ->attach(new ViewResolver\RelativeFallbackResolver($pathResolver))
            ->attach(
                new ViewResolver\RelativeFallbackResolver($prefixPathStackResolver)
            );

        return $resolver;
    }
}
