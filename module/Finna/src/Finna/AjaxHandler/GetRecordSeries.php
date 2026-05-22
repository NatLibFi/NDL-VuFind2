<?php

/**
 * "Get Record Series" AJAX handler.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Record\Loader;

/**
 * "Get Record Series" AJAX handler.
 *
 * Get series tab data
 *
 * @category VuFind
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetRecordSeries extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Record loader.
     *
     * @var Loader
     */
    protected $recordLoader;

    /**
     * View renderer.
     *
     * @var \Laminas\View\Renderer\RendererInterface
     */
    protected $renderer;

    /**
     * Constructor.
     *
     * @param Loader            $loader   Record loader
     * @param RendererInterface $renderer Renderer
     */
    public function __construct(
        Loader $loader,
        RendererInterface $renderer
    ) {
        $this->recordLoader = $loader;
        $this->renderer = $renderer;
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
        $seriesKey = $params->fromQuery('seriesKey');
        if (empty($seriesKey)) {
            return $this->formatResponse('', self::STATUS_HTTP_BAD_REQUEST);
        }
        $driver = $this->recordLoader->load($params->fromQuery('id'), $params->fromQuery('source'));
        $html = $this->renderer->render('RecordTab/series.phtml', ['seriesKey' => $seriesKey, 'driver' => $driver]);
        return $this->formatResponse(compact('html'));
    }
}
