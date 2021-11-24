<?php

/**
 * Organisations list view helper
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2021.
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
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

use Finna\OrganisationInfo\OrganisationInfo;
use Finna\Search\Solr\HierarchicalFacetHelper;
use Laminas\Cache\Storage\StorageInterface;
use VuFind\Search\Results\PluginManager;

/**
 * Organisations list view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class OrganisationsList extends \Laminas\View\Helper\AbstractHelper implements
    \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Current locale
     *
     * @var string
     */
    protected $locale;

    /**
     * Cache
     *
     * @var StorageInterface
     */
    protected $cache;

    /**
     * Hierarchial facet helper
     *
     * @var HierarchicalFacetHelper
     */
    protected $facetHelper;

    /**
     * Search result plugin manager
     *
     * @var PluginManager
     */
    protected $resultsManager;

    /**
     * Organisation info service
     *
     * @var OrganisationInfo
     */
    protected $organisationInfo;

    /**
     * Constructor
     *
     * @param StorageInterface        $cache            Cache
     * @param HierarchicalFacetHelper $facetHelper      Facet helper
     * @param PluginManager           $resultsManager   Search result manager
     * @param OrganisationInfo        $organisationInfo Organisation info service
     * @param string                  $locale           Current locale
     */
    public function __construct(
        StorageInterface $cache,
        HierarchicalFacetHelper $facetHelper,
        PluginManager $resultsManager,
        OrganisationInfo $organisationInfo,
        string $locale
    ) {
        $this->cache = $cache;
        $this->facetHelper = $facetHelper;
        $this->resultsManager = $resultsManager;
        $this->organisationInfo = $organisationInfo;
        $this->locale = $locale;
    }

    /**
     * List of current organisations.
     *
     * @return array
     */
    public function __invoke()
    {
        $cacheName = 'organisations_list_' . $this->locale;
        $list = $this->cache->getItem($cacheName);

        if (!$list) {
            $emptyResults = $this->resultsManager->get('EmptySet');

            try {
                $sectorFacets = $this->getFacetList('sector_str_mv');

                foreach ($sectorFacets as $sectorFacet) {
                    $sectorParts = explode('/', $sectorFacet['value']);
                    $sectorParts = array_splice($sectorParts, 1, -1);
                    $sector = implode('/', $sectorParts);
                    $list[$sector] = [];

                    $collection = $this->getFacetList(
                        'building',
                        '0/',
                        'sector_str_mv:' . $sectorFacet['value']
                    );

                    foreach ($collection as $item) {
                        $link = $emptyResults->getUrlQuery()
                            ->addFacet('building', $item['value'])->getParams();
                        $displayText = $item['displayText'];
                        if ($displayText == $item['value']) {
                            $displayText = $this->facetHelper
                                ->formatDisplayText($displayText)
                                ->getDisplayString();
                        }
                        $organisationInfoId
                            = $this->organisationInfo->getOrganisationInfoId(
                                $item['value']
                            );

                        $list[$sector][] = [
                            'name' => $displayText,
                            'link' => $link,
                            'organisation' => $organisationInfoId,
                            'sector' => $sector
                        ];
                    }
                    $collator = \Collator::create($this->locale);
                    $collator->sort($list[$sector]);
                }
                $this->cache->setItem($cacheName, $list);
            } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
                $this->logError(
                    'Error creating organisations list: ' . $e->getMessage()
                );
                throw $e;
            }
        }

        return $list;
    }

    /**
     * Get facet data from a field
     *
     * @param string $field  Field to return
     * @param string $prefix Optional facet prefix limiter
     * @param string $filter Optional filter
     *
     * @return array
     */
    protected function getFacetList(
        string $field,
        string $prefix = '',
        string $filter = ''
    ): array {
        $results = $this->resultsManager->get('Solr');
        $params = $results->getParams();
        // Disable deduplication so that facet results are not affected:
        $params->addFilter('finna.deduplication:"0"');
        $params->setLimit(0);
        $params->setFacetLimit(-1);
        if ('' !== $prefix) {
            $params->setFacetPrefix($prefix);
        }
        $options = $params->getOptions();
        $options->disableHighlighting();
        $options->spellcheckEnabled(false);

        $params->addFacet($field, $field, false);
        if ('' !== $filter) {
            $params->addFilter($filter);
        }
        $facetList = $results->getFacetList();
        return $facetList[$field]['list'] ?? [];
    }
}
