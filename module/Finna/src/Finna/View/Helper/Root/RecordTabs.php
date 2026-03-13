<?php

/**
 * Record tab view helper
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\View\Helper\Root;

use function count;

/**
 * Record tab view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class RecordTabs extends \VuFind\View\Helper\Root\RecordTabs
{
    /**
     * Transform record tabs to general tabs array.
     *
     * @param \VuFind\RecordDriver\AbstractBase $driver    Record driver
     * @param array                             $tabs      Tabs
     * @param string                            $activeTab Active tab
     *
     * @return array
     */
    public function getTabs(
        \VuFind\RecordDriver\AbstractBase $driver,
        array $tabs,
        string $activeTab
    ): array {
        $result = parent::getTabs($driver, $tabs, $activeTab);
        if (isset($result['usercomments'])) {
            $recordHelper = $this->getView()->plugin('record');
            $result['usercomments']['count'] = count($recordHelper($driver)->getComments());
        }
        if (isset($result['details'])) {
            $recordHelper = $this->getView()->plugin('icon');
            $result['details']['description'] = '';
            $result['details']['icon'] = [
                'name' => 'staff-view',
                'class' => 'staff-view-icon',
            ];
        }
        return $result;
    }
}
