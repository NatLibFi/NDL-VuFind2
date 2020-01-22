<?php
/**
 * Authority Record Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2019-20.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Controller;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Authority Record Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class AuthorityRecordController extends RecordController
{
    /**
     * Load the record requested by the user; note that this is not done in the
     * init() method since we don't want to perform an expensive search twice
     * when homeAction() forwards to another method.
     *
     * @return AbstractRecordDriver
     */
    protected function loadRecord()
    {
        // Temporarily switch searchClassId to get results from Authority index.
        // This way we can render searchbox.phtml with Solr searchClassId
        // so that a new search from AuthorityRecord page is sent to Solr.
        $this->searchClassId = 'SolrAuth';
        $rec = parent::loadRecord();
        $this->searchClassId = 'Solr';
        return $rec;
    }

    /**
     * Record action -- display a record
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function recordAction()
    {
        return $this->forwardTo(
            'authorityrecord', 'home', ['id' => $this->params()->fromQuery('id')]
        );
    }
}
