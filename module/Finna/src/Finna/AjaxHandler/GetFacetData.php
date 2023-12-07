<?php

/**
 * "Get Facet Data" AJAX handler
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2018.
 * Copyright (C) The National Library of Finland 2018.
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
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use Laminas\Config\Config;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Search\Results\PluginManager as ResultsManager;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use VuFind\Session\Settings as SessionSettings;

/**
 * "Get Facet Data" AJAX handler
 *
 * Get hierarchical facet data for jsTree
 *
 * Parameters:
 * facetName  The facet to retrieve
 * facetSort  By default all facets are sorted by count. Two values are available
 * for alternative sorting:
 *   top = sort the top level alphabetically, rest by count
 *   all = sort all levels alphabetically
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetFacetData extends \VuFind\AjaxHandler\GetFacetData
{
    use BrowseActionTrait;

    /**
     * Browse configuration
     *
     * @var Config
     */
    protected $browseConfig;

    /**
     * Facet configuration
     *
     * @var Config
     */
    protected $facetConfig;

    /**
     * Constructor
     *
     * @param SessionSettings         $ss Session settings
     * @param HierarchicalFacetHelper $fh Facet helper
     * @param ResultsManager          $rm Search results manager
     * @param Config                  $bc Browse configuration
     */
    public function __construct(
        SessionSettings $ss,
        HierarchicalFacetHelper $fh,
        ResultsManager $rm,
        Config $bc
    ) {
        parent::__construct($ss, $fh, $rm);
        $this->browseConfig = $bc;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $request = $params->getController()->getRequest();
        if ($type = $this->getBrowseAction($request)) {
            if (!isset($this->browseConfig[$type])) {
                return $this->formatResponse(
                    "Missing configuration for browse action: $type",
                    self::STATUS_HTTP_ERROR
                );
            }

            $config = $this->browseConfig[$type];
            $query = $request->getQuery();
            if (!$query->get('sort')) {
                $query->set('sort', $config['sort'] ?: 'title');
            }
            if (!$query->get('type')) {
                $query->set('type', $config['type'] ?: 'Title');
            }
            $query->set('browseHandler', $query->get('type'));
            $query->set('hiddenFilters', $config['filter']->toArray());
        }

        return parent::handleRequest($params);
    }
}
