<?php
/**
 * Search API Controller
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace FinnaApi\Controller;

use Finna\Controller\ListController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\Parameters;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\RecordMissing as RecordMissingException;
use VuFindApi\Controller\ApiInterface;
use VuFindApi\Controller\ApiTrait;
use VuFindApi\Formatter\RecordFormatter;

/**
 * List API Controller
 *
 * Controls the List API functionality
 *
 * @category VuFind
 * @package  Service
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class ListApiController extends ListController implements ApiInterface
{
    use ApiTrait;

    /**
     * Record formatter
     *
     * @var RecordFormatter
     */
    protected $recordFormatter;

    /**
     * Default record fields to return if a request does not define the fields
     *
     * @var array
     */
    protected $defaultRecordFields = [];

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     * @param RecordFormatter         $rf Record formatter
     */
    public function __construct(ServiceLocatorInterface $sm, RecordFormatter $rf)
    {
        parent::__construct($sm);
        $this->recordFormatter = $rf;
        foreach ($rf->getRecordFields() as $fieldName => $fieldSpec) {
            if (!empty($fieldSpec['vufind.default'])) {
                $this->defaultRecordFields[] = $fieldName;
            }
        }
    }

    /**
     * Get Swagger specification JSON fragment for services provided by the
     * controller
     *
     * @return string
     */
    public function getSwaggerSpecFragment()
    {
        // TODO: Implement getSwaggerSpecFragment() method.
        return '';
    }

    /**
     * List action
     *
     * @return \Laminas\Http\Response
     */
    public function listAction()
    {
        $requestParams = $this->getRequest()->getQuery()->toArray()
            + $this->getRequest()->getPost()->toArray();

        $id = $requestParams['id'] ?? false;
        if (!$id) {
            return $this->output([], self::STATUS_ERROR, 400, 'Missing id');
        }

        try {
            $list = $this->getTable('UserList')->getExisting($id);
            if (!$list->isPublic()) {
                return $this->output(
                    [], self::STATUS_ERROR, 403, 'Permission denied'
                );
            }
        } catch (RecordMissingException $e) {
            return $this->output([], self::STATUS_ERROR, 404, 'List not found');
        }

        try {
            $results = $this->serviceLocator
                ->get(\VuFind\Search\Results\PluginManager::class)->get('Favorites');
            $params = $results->getParams();

            $params->initFromRequest(new Parameters($requestParams));

            $results->performAndProcessSearch();
            $listObj = $results->getListObject();

            $response = [
                'list' => [
                    'id' => $listObj->id,
                    'title' => $listObj->title,
                    'description' => $listObj->description
                    // TODO: Add all list data to response
                ]
            ];

            if ($this->listTagsEnabled()) {
                $listTags = $this->getTable('Tags')
                    ->getForList($listObj->id, $listObj->user_id);
                // TODO: Add list tags to response
            }

            $response['resultCount'] = $results->getResultTotal();

            $records = $this->recordFormatter->format(
                $results->getResults(), $this->defaultRecordFields
            );
            if ($records) {
                $response['records'] = $records;
                $response['notes'] = [];
                foreach ($results->getResults() as $result) {
                    $response['notes'][] = $result->getListNotes($listObj->id);
                }
            }

            return $this->output($response, self::STATUS_OK);
        } catch (ListPermissionException $e) {
            return $this->output([], self::STATUS_ERROR, 403, 'Permission denied');
        }
    }
}
