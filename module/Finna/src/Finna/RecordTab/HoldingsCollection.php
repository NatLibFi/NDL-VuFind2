<?php

/**
 * SolrEad3 External data tab.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2018-2020.
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
 * @package  RecordTabs
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_tabs Wiki
 */

namespace Finna\RecordTab;

/**
 * SolrEad3 External data tab.
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_tabs Wiki
 */
class HoldingsCollection extends \VuFind\RecordTab\AbstractBase
{

    protected $openUrlHelper;

    protected $recordHelper;

    public function __construct($recordHelper, $openUrlHelper)
    {
        $this->openUrlHelper = $openUrlHelper;
        $this->recordHelper = $recordHelper;
    }
    /**
     * Is this tab active?
     *
     * @return bool
     */
    public function isActive()
    {
        $driver = $this->getRecordDriver();
        $openUrlActive = ($this->openUrlHelper)($driver, 'holdings');
        $hasLinks = ($this->recordHelper)($driver)->getLinkDetails($openUrlActive);
        return $this->displayManifestationPart()
            || $driver->tryMethod('archiveRequestAllowed')
            || $hasLinks;
    }

    /**
     * Display manifestation information?
     *
     * @return bool
     */
    public function displayManifestationPart()
    {
        $data = $this->driver->tryMethod('getExternalData');
        return !empty($data['items']);
    }

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'holdings_collection';
    }
}
