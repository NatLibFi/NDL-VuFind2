<?php

/**
 * Helper to check if current user is authenticated with Mozilla Persona
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2015.
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
 * @author   Jyrki Messo <jyrki.messo@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * Helper to check if current user is authenticated with Mozilla Persona
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Jyrki Messo <jyrki.messo@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class StreetSearch extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Configuration
     *
     * @var \Zend\Config\Config
     */
    
    protected $config;

    /**
     * Constructor
     *
     * @param type $serviceLocator Service locator
     */
    public function __construct(\Zend\Config\Config $config)
    {
        $this->config = $config;
    }

    /**
     * This component outputs code Finna Street launch button
     *
     * @return null|string 
     */
    public function renderStreetSearchButton()
    {
        return $this->getView()->render('Helpers/streetsearch.phtml');
    }

}
