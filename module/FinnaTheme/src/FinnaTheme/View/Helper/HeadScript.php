<?php
/**
 * Head script view helper (extended for VuFind's theme system)
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace FinnaTheme\View\Helper;

/**
 * Head script view helper (extended for VuFind's theme system)
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class HeadScript extends \VuFindTheme\View\Helper\HeadScript
{
    /**
     * Retrieve string representation
     *
     * @param string|int $indent Amount of whitespaces or string to use for indention
     *
     * @return string
     */
    public function toString($indent = null)
    {
        foreach ($this as &$item) {
            if (($item->type ?? '') === 'text/javascript') {
                $item->type = '';
            }
        }

        if ($this->view) {
            $doctype = $this->view->plugin('doctype');
            $doctype->setDoctype($doctype::HTML5);
        }

        return parent::toString($indent);
    }
}
