<?php
/**
 * Finna personal information support trait.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2021.
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
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace Finna\Controller;

/**
 * Finna personal information support trait.
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
trait FinnaPersonalInformationSupportTrait
{
    /**
     * Order available requests to beginning of the record list
     *
     * @param array $recordList List to order
     *
     * @return array
     */
    protected function orderAvailability($recordList)
    {
        if ($recordList === null) {
            return [];
        }

        $availableRecordList = [];
        $recordListBasic = [];
        foreach ($recordList as $item) {
            if (isset($item->getExtraDetail('ils_details')['available'])
                && $item->getExtraDetail('ils_details')['available']
            ) {
                $availableRecordList[] = $item;
            } else {
                $recordListBasic[] = $item;
            }
        }
        return array_merge($availableRecordList, $recordListBasic);
    }

    /**
     * Get account blocks if supported by the ILS
     *
     * @param array $patron Patron
     *
     * @return array
     */
    protected function getAccountBlocks($patron)
    {
        $catalog = $this->getILS();
        if ($catalog->checkCapability('getAccountBlocks', compact('patron'))
            && $blocks = $catalog->getAccountBlocks($patron)
        ) {
            return $blocks;
        }
        return [];
    }
}
