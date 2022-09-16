<?php
/**
 * Session view helper
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Aida Luuppala <aida.luuppala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * Session view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Aida Luuppala <aida.luuppala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class BazaarSession extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Session configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $config;

    /**
     * Session container
     *
     * @var Container
     */
    protected $session;

    /**
     * Session container name.
     *
     * @var string
     */
    public const SESSION_NAME = 'BazaarSession';

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config     $config  Session configuration
     * @param \Laminas\Session\Container $session Session container
     */
    public function __construct(
        \Laminas\Config\Config $config,
        \Laminas\Session\Container $session
    ) {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Get value from this session container by key name.
     *
     * @param string $name Session variable's key name
     *
     * @return mixed
     */
    public function get($name)
    {
        return $this->session[$name];
    }

    /**
     * Checks if uuid is set to session.
     *
     * @return bool
     */
    public function isSelectionOngoing()
    {
        return ($this->get('uuid')) ? true : false;
    }
}
