<?php
/**
 * Helper class for restricted Solr R2 search.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

use Finna\Service\RemsService;

/**
 * Helper class for restricted Solr R2 search.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class R2 extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Is R2 search enabled?
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Is user authenticated to use R2?
     *
     * @var bool
     */
    protected $authenticated;

    /**
     * RemsService
     *
     * @var RemsService
     */
    protected $rems;

    /**
     * Constructor
     *
     * @param bool        $enabled       Is R2 enabled?
     * @param bool        $authenticated Is user authenticated to use R2?
     * @param RemsService $rems          RemsService
     */
    public function __construct(
        bool $enabled, bool $authenticated, RemsService $rems
    ) {
        $this->enabled = $enabled;
        $this->authenticated = $authenticated;
        $this->rems = $rems;
    }

    /**
     * Check if R2 is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        return (bool)$this->enabled;
    }

    /**
     * Check if user is authenticated to use R2.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * Check if user is registered to REMS during this session.
     *
     * @param bool $checkEntitlements Also check entitlements?
     *
     * @return bool
     */
    public function isRegistered($checkEntitlements = false)
    {
        return $this->rems->isUserRegisteredDuringSession($checkEntitlements);
    }

    /**
     * Check if user is has access to R2
     *
     * @param bool $ignoreCache Ignore cache?
     *
     * @return bool
     */
    public function hasUserAccess($ignoreCache = true)
    {
        return $this->rems->hasUserAccess($ignoreCache);
    }
}
