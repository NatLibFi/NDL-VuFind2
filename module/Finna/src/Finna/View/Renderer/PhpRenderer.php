<?php
/**
 * Finna PHP template renderer.
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
 * @package  Renderers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\View\Renderer;

use Laminas\View\Model\ModelInterface as Model;

/**
 * Finna PHP template renderer.
 *
 * @category VuFind
 * @package  Renderers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class PhpRenderer extends \Laminas\View\Renderer\PhpRenderer
{
    /**
     * Processes a view script and returns the output.
     *
     * @param string|Model           $nameOrModel Either the template to use, or a
     * ViewModel. The ViewModel must have the template as an option in order to be
     * valid.
     * @param null|array|Traversable $values      Values to use when rendering. If
     * none provided, uses those in the composed variables container.
     *
     * @return string The script output.
     * @throws Exception\DomainException if a ViewModel is passed, but does not
     *                                   contain a template option.
     * @throws Exception\InvalidArgumentException if the values passed are not
     *                                            an array or ArrayAccess object
     * @throws Exception\RuntimeException if the template cannot be rendered
     */
    public function render($nameOrModel, $values = null)
    {
        if ($nameOrModel instanceof Model) {
            $nameOrModel->setTemplate(
                $this->expandName($nameOrModel->getTemplate())
            );
        } else {
            $nameOrModel = $this->expandName($nameOrModel);
        }
        return parent::render($nameOrModel, $values);
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
