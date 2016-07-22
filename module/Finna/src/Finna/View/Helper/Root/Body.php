<?php
/**
 * HTML body view helper
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2016.
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
 * @package  View_Helpers
 * @author   Timo Laine <timo.mz.laine@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * HTML body view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Timo Laine <timo.mz.laine@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Body extends \Zend\View\Helper\AbstractHelper
{
  
    /**
     * HTML body classes
     *
     * @var array
     */
    protected $classes;
  
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->classes = [];
    }  
  
    /**
     * Appends a class to HTML body
     *
     * @param string $class   Class to add
     *
     * @return array
     */
    public function appendClass($class)
    {
        if (!in_array($class, $this->classes)) {
            $this->classes[] = $class;
        }
        
        return $this->classes;
    }
    
    /**
     * Returns HTML body classes
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

}
