<?php

/**
 * AJAX handler for getting authority information for recommendations.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2019.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use Finna\Recommend\AuthorityRecommend;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Db\Service\SearchServiceInterface;

/**
 * AJAX handler for getting authority information for recommendations.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetAuthorityFullInfo extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Constructor
     *
     * @param RendererInterface                    $renderer           View renderer
     * @param AuthorityRecommend                   $authorityRecommend Authority Recommend
     * @param \VuFind\Search\Results\PluginManager $resultsManager     Search results manager
     * @param SearchServiceInterface               $searchService      Search database service
     * @param \Laminas\Session\Container           $session            Session
     * @param \Laminas\Session\SessionManager      $sessionManager     Session manager
     * manager
     */
    public function __construct(
        protected RendererInterface $renderer,
        protected AuthorityRecommend $authorityRecommend,
        protected \VuFind\Search\Results\PluginManager $resultsManager,
        protected SearchServiceInterface $searchService,
        protected \Laminas\Session\Container $session,
        protected \Laminas\Session\SessionManager $sessionManager
    ) {
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
        $id = $params->fromQuery('id');
        $searchId = $params->fromQuery('searchId');

        if (!$id || !$searchId) {
            return $this->formatResponse('', self::STATUS_HTTP_BAD_REQUEST);
        }
        $sessId = $this->sessionManager->getId();
        $search = $this->searchService->getSearchByIdAndOwner($searchId, $sessId, null);
        if (empty($search)) {
            return $this->formatResponse(
                'Search not found',
                self::STATUS_HTTP_BAD_REQUEST
            );
        }

        $minSO = $search->getSearchObject();
        $savedSearch = $minSO->deminify($this->resultsManager);
        $searchParams = $savedSearch->getParams();

        $this->authorityRecommend->init($searchParams, $params->getController()->getRequest());
        $this->authorityRecommend->process($savedSearch);
        $recommendations = $this->authorityRecommend->getRecommendations();

        $authority = end($recommendations);
        foreach ($recommendations as $rec) {
            if ($rec->getUniqueID() === $id) {
                $authority = $rec;
                break;
            }
        }

        // Save active author ID and active authority filters
        $this->session->activeId = $id;
        $this->session->idsWithRoles = $searchParams->getAuthorIdFilter(true);

        $html = $this->renderer->partial(
            'ajax/authority-recommend.phtml',
            ['recommend' => $this->authorityRecommend, 'authority' => $authority]
        );

        return $this->formatResponse(compact('html'));
    }
}
