<?php

/**
 * Solr Search Parameters
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Search\SolrCollection;

use function is_array;
use function strlen;

/**
 * Solr Search Parameters
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Params extends \VuFind\Search\SolrCollection\Params
{
    use \Finna\Search\FinnaParams;

    /**
     * Applied filters
     *
     * @var array
     */
    protected $filterList = [];

    /**
     * Date converter
     *
     * @var \Vufind\Date\Converter
     */
    protected $dateConverter;

    // Date range index field (VuFind1)
    public const SPATIAL_DATERANGE_FIELD_VF1 = 'search_sdaterange_mv';

    // Default daterange type value
    public const DATERANGE_DEFAULT_TYPE = 'overlap';

    /**
     * Does the object already contain the specified filter?
     *
     * @param string $filter A filter string from url : "field:value"
     *
     * @return void
     */
    public function addFilter($filter)
    {
        // Extract field and value from URL string:
        [$field, $value] = $this->parseFilter($filter);

        if (
            $field == $this->getDateRangeSearchField()
            || $field == self::SPATIAL_DATERANGE_FIELD_VF1
        ) {
            // Date range filters are processed
            // separately (see initSpatialDateRangeFilter)
            return;
        }
        parent::addFilter($filter);
    }

    /**
     * Add filters to the object based on values found in the request object.
     *
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    protected function initFilters($request)
    {
        parent::initFilters($request);
        $this->initSpatialDateRangeFilter($request);
    }

    /**
     * Initialize date range filter (search_daterange_mv)
     *
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function initSpatialDateRangeFilter($request)
    {
        $dateRangeField = $this->getDateRangeSearchField();
        if (!$dateRangeField) {
            return;
        }
        $type = $request->get("{$dateRangeField}_type");
        if (!$type) {
            $type = self::DATERANGE_DEFAULT_TYPE;
        }

        $from = $to = null;
        $found = false;
        // Date range filter
        if (($reqFilters = $request->get('filter')) && is_array($reqFilters)) {
            foreach ($reqFilters as $f) {
                [$field, $value] = $this->parseFilter($f);
                if (
                    $field == $dateRangeField
                    || $field == self::SPATIAL_DATERANGE_FIELD_VF1
                ) {
                    if ($range = $this->parseDateRangeFilter($f)) {
                        $from = $range['from'];
                        $to = $range['to'];
                        if (
                            isset($range['type'])
                            && $range['type'] !== self::DATERANGE_DEFAULT_TYPE
                        ) {
                            $type = $range['type'];
                        }
                        $found = true;
                        break;
                    }
                }
            }
        }

        if (!$found) {
            return;
        }

        // Add filter. The final Solr filter is constructed in getFilterSettings.
        $filter = "$dateRangeField:$type|[$from TO $to]";
        parent::addFilter($filter);
    }

    /**
     * Return current date range filter.
     *
     * @return mixed false|array Filter
     */
    public function getDateRangeFilter()
    {
        $filterList = $this->getFilterList();
        foreach ($filterList as $facet => $filters) {
            foreach ($filters as $filter) {
                if ($this->isDateRangeFilter($filter['field'])) {
                    return $filter;
                }
            }
        }
        return false;
    }

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        $result = parent::getFilterSettings();

        // Special processing for date range filters
        $dateRangeField = $this->getDateRangeSearchField();
        if ($dateRangeField) {
            foreach ($result as &$filter) {
                $dateRange = strncmp(
                    $filter,
                    "$dateRangeField:",
                    strlen($dateRangeField) + 1
                ) == 0;
                if ($dateRange) {
                    [, $value] = $this->parseFilter($filter);
                    [$op, $range] = explode('|', $value);
                    $op = $op == 'within' ? 'Within' : 'Intersects';
                    $filter = "{!field f=$dateRangeField op=$op}$range";
                }
            }
        }
        return $result;
    }
}
