<?php

/**
 * Search API Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2016.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
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

namespace FinnaApi\Controller;

/**
 * Search API Controller
 *
 * Controls the Search API functionality
 *
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class SearchApiController extends \VuFindApi\Controller\SearchApiController
{
    /**
     * Get API specification JSON fragment for services provided by the
     * controller
     *
     * @return string
     */
    public function getApiSpecFragment()
    {
        $spec = json_decode(parent::getApiSpecFragment(), true);

        $spec['paths']['/record']['get']['description']
            = $this->getViewRenderer()->render('searchapi/record-description.phtml');
        $spec['paths']['/search']['get']['description']
            = $this->getViewRenderer()->render('searchapi/search-description.phtml');
        foreach ($spec['paths']['/search']['get']['parameters'] as &$param) {
            if ('facet[]' === $param['name']) {
                $param['description'] = '';
            }
        }
        unset($param);

        $overrides = $this->getViewRenderer()
            ->render('searchapi/openapi-overrides.phtml');

        $spec = array_merge_recursive($spec, json_decode($overrides, true));
        ksort($spec['components']['schemas']);

        return json_encode($spec);
    }
}
