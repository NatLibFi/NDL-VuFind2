<?php

/**
 * Hierarchy Tree Data Source (Solr).
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
 * @package  HierarchyTree_DataSource
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */

namespace Finna\Hierarchy\TreeDataSource;

/**
 * Hierarchy Tree Data Source (Solr).
 *
 * This is a base helper class for producing hierarchy Trees.
 *
 * @category VuFind
 * @package  HierarchyTree_DataSource
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
class Solr extends \VuFind\Hierarchy\TreeDataSource\Solr
{
    /**
     * Get default search parameters shared by cursorMark and legacy methods.
     *
     * @return array
     */
    protected function getDefaultSearchParams(): array
    {
        $result = parent::getDefaultSearchParams();
        $result['fl'] = ['title,id,hierarchy_parent_id,hierarchy_top_id,'
                . 'is_hierarchy_id,hierarchy_sequence,title_in_hierarchy,'
                . 'title_en_txt,title_fi_txt,title_se_txt,title_sv_txt,'
                . 'title_in_hierarchy_en_str_mv,title_in_hierarchy_fi_str_mv,'
                . 'title_in_hierarchy_se_str_mv,title_in_hierarchy_sv_str_mv'];
        return $result;
    }
}
