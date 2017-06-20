<?php
/**
 * EDS Controller
 *
 * PHP version 5
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Anna Niku <anna.niku@gofore.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace Finna\Controller;

/**
 * EDS Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Anna Niku <anna.niku@gofore.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class EdsController extends \VuFind\Controller\EdsController
{
    use SearchControllerTrait;

    /**
     * Save a search to the history in the database.
     * Save search Id and type to memory
     *
     * @param \VuFind\Search\Base\Results $results Search results
     *
     * @return void
     */
    public function saveSearchToHistory($results)
    {
        parent::saveSearchToHistory($results);
        $this->getSearchMemory()->rememberSearchData(
            $results->getSearchId(),
            $results->getParams()->getSearchType(),
            $results->getUrlQuery()->isQuerySuppressed()
                ? '' : $results->getParams()->getDisplayQuery()
        );
    }

    /**
     * Get the search memory
     *
     * @return \Finna\Search\Memory
     */
    public function getSearchMemory()
    {
        return $this->serviceLocator->get('Finna\Search\Memory');
    }
}
