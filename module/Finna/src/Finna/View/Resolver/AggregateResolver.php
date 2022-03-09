<?php
/**
 * Finna aggregate resolver.
 *
 * PHP version 7
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

use Laminas\View\Renderer\RendererInterface as Renderer;

/**
 * Finna aggregate resolver.
 *
 * @category VuFind
 * @package  Resolvers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class AggregateResolver extends \Laminas\View\Resolver\AggregateResolver
{
    /**
     * Resolve a template/pattern name to a resource the renderer can consume
     *
     * @param string        $name     Name
     * @param null|Renderer $renderer Renderer
     *
     * @return false|string
     */
    public function resolve($name, Renderer $renderer = null)
    {
        return parent::resolve($this->expandName($name), $renderer);
    }

    /**
     * Expands a component template name if the name only contains a path to a
     * component folder.
     *
     * @param string $name Name
     *
     * @return string
     */
    protected function expandName(string $name): string
    {
        if (!empty($name)
            && strpos($name, 'components/') === 0
            && substr($name, -6) !== '.phtml'
        ) {
            $parts = explode('/', $name);
            $last = array_pop($parts);
            if ($last !== array_pop($parts)) {
                $name = $name . '/' . $last . '.phtml';
            }
        }
        return $name;
    }
}
