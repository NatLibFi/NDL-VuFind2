<?php
/**
 * CleanHtml helper factory.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @package  View_Helpers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\View\Helper\Root;

use Finna\View\CustomElement\CustomElementInterface;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * CleanHtml helper factory.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class CleanHtmlFactory implements FactoryInterface
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
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        $cacheDir = $container->get(\VuFind\Cache\Manager::class)
            ->getCache('object')->getOptions()->getCacheDir();

        $config = $container->get('config');
        $customElements
            = $config['vufind']['plugin_managers']['view_customelement']['aliases'];

        return new $requestedName(
            $cacheDir,
            self::getAllowedElements($customElements)
        );
    }

    /**
     * Returns an array containing HTML Purifier compatible information about all
     * allowed HTML elements, based on the provided array of custom elements.
     *
     * @param array $customElements Custom elements
     *
     * @return array
     */
    public static function getAllowedElements($customElements)
    {
        $attrs = CustomElementInterface::ATTRIBUTES;
        $allowedElements = [];
        foreach ($customElements as $elementName => $elementClass) {
            $allowedElements[$elementName] = $elementClass::getInfo();
            foreach ($elementClass::getChildInfo() as $childName => $childInfo) {
                if (isset($allowedElements[$childName][$attrs])) {
                    $childInfo[$attrs] = array_merge(
                        $allowedElements[$childName][$attrs],
                        $childInfo[$attrs]
                    );
                }
                $allowedElements[$childName] = $childInfo;
            }
        }
        return $allowedElements;
    }
}