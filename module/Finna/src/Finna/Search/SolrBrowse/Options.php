<?php

/**
 * SolrBrowse aspect of the Search Multi-class (Options)
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
 * @package  Search_Solr
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Search\SolrBrowse;

/**
 * SolrBrowse Search Options
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Options extends \Finna\Search\Solr\Options
{
    /**
     * Overall default sort option
     *
     * @var string
     */
    protected $defaultSort = 'title,id asc';

    /**
     * Default search handler
     *
     * @var string
     */
    protected $defaultHandler = 'Title';

    /**
     * Default limit option
     *
     * @var int
     */
    protected $defaultLimit = 100;

    /**
     * Default view option
     *
     * @var string
     */
    protected $defaultView = 'condensed';

    /**
     * Spelling setting
     *
     * @var bool
     */
    protected $spellcheck = false;

    /**
     * Configuration file to read search settings from
     *
     * @var string
     */
    protected $searchIni = 'browse';

    /**
     * Configuration file to read facet settings from
     *
     * @var string
     */
    protected $facetsIni = 'facets-browse';

    /**
     * Browse type
     *
     * @var string
     */
    protected $browseType = '';

    /**
     * Return the route name for the search results action.
     *
     * @return string
     */
    public function getSearchAction()
    {
        return 'browse-' . $this->browseType;
    }

    /**
     * Set curren browse type
     *
     * @param string $type Browse type
     *
     * @return void
     */
    public function setBrowseType(string $type): void
    {
        $this->browseType = $type;
    }
}
