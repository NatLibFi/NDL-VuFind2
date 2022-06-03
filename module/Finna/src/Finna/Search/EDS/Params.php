<?php
/**
 * EDS API Params
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2017.
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
 * @package  EBSCO
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace Finna\Search\EDS;

/**
 * EDS API Params
 *
 * @category VuFind
 * @package  EBSCO
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Params extends \VuFind\Search\EDS\Params
{
    use \Finna\Search\FinnaParams;

    /**
     * Whether to request checkbox facet counts
     *
     * @var bool
     */
    protected $checkboxFacetCounts = false;

    /**
     * Whether to request checkbox facet counts
     *
     * @return bool
     */
    public function getCheckboxFacetCounts()
    {
        return $this->checkboxFacetCounts;
    }

    /**
     * Whether to request checkbox facet counts
     *
     * @param bool $value Enable or disable
     *
     * @return void
     */
    public function setCheckboxFacetCounts($value)
    {
        $this->checkboxFacetCounts = $value;
    }

    /**
     * Get the full facet settings stored by addFacet -- these may include extra
     * parameters needed by the search results class.
     *
     * @return array
     */
    public function getFullFacetSettings()
    {
        $result = $this->fullFacetSettings;

        if ($this->checkboxFacetCounts && !empty($this->checkboxFacets)) {
            $result = array_merge($result, array_keys($this->checkboxFacets));
        }

        return $result;
    }
}
