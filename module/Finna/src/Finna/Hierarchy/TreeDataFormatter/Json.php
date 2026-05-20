<?php

/**
 * Hierarchy Tree Data Formatter (JSON).
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
 * @package  HierarchyTree_DataFormatter
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */

namespace Finna\Hierarchy\TreeDataFormatter;

use function count;
use function is_array;

/**
 * Hierarchy Tree Data Formatter (JSON).
 *
 * @category VuFind
 * @package  HierarchyTree_DataFormatter
 * @author   Minna Rönkä <minna.ronka@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
class Json extends \VuFind\Hierarchy\TreeDataFormatter\Json
{
    /**
     * Get Solr Children for JSON.
     *
     * @param object $record   Solr record to format
     * @param string $parentID The starting point for the current recursion
     * (equivalent to Solr field hierarchy_parent_id)
     *
     * @return string
     */
    protected function formatNode($record, $parentID = null)
    {
        $result = (object)parent::formatNode($record, $parentID);
        $result->titles = $this->pickLanguageTitles($record, $parentID);
        return $result;
    }

    /**
     * Get language versions of the record title.
     * See also \VuFind\Hierarchy\TreeDataFormatter::pickTitle().
     *
     * @param object  $record   Solr record to format
     * @param ?string $parentID The starting point for the current recursion
     * (equivalent to Solr field hierarchy_parent_id)
     *
     * @return array
     */
    protected function pickLanguageTitles(object $record, ?string $parentID): array
    {
        $results = [];

        foreach (['fi', 'sv', 'en-gb', 'se'] as $language) {
            if (null !== $parentID) {
                // For others than hierarchy top record, use language versions of title_in_hierarchy if available.
                $titles = $this->getLanguageTitlesInHierarchy($record, $language);
                if (isset($titles[$parentID])) {
                    $results[$language] = $titles[$parentID];
                    continue;
                }
            }
            // Check language versions of title field.
            $field = 'title_' . substr($language, 0, 2) . '_txt';
            if ($record->$field ?? '') {
                $results[$language] = $record->$field;
            }
        }
        return $results;
    }

    /**
     * Get the language versions of the titles of this item within parent collections.
     * Returns an array of parent ID => sequence number.
     * See also \VuFind\Hierarchy\TreeDataFormatter::getTitlesInHierarchy().
     *
     * @param object $fields   Solr fields
     * @param string $language Language code
     *
     * @return array
     */
    protected function getLanguageTitlesInHierarchy(object $fields, string $language): array
    {
        $retVal = [];
        $field = 'title_in_hierarchy_' . substr($language, 0, 2) . '_str_mv';
        if (
            null !== ($titles = $fields->$field ?? null)
            && is_array($titles)
        ) {
            $parentIDs = (array)($fields->hierarchy_parent_id ?? []);
            if (count($titles) === count($parentIDs)) {
                foreach ($parentIDs as $key => $val) {
                    $retVal[$val] = $titles[$key];
                }
            }
        }
        return $retVal;
    }
}
