<?php
/**
 * 3D model ajax handler.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Finna\File\Loader as FileLoader;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Record\Loader as RecordLoader;
use VuFind\Session\Settings as SessionSettings;
use Laminas\Router\Http\TreeRouteStack;

/**
 * GetModel AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetModel extends \VuFind\AjaxHandler\AbstractBase
    implements \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Session settings
     *
     * @var Settings
     */
    protected $sessionSettings;

    /**
     * Loader
     *
     * @var RecordLoader
     */
    protected $recordLoader;

    /**
     * File loader
     *
     * @var Loader
     */
    protected $fileLoader;

    /**
     * Domain url
     *
     * @var string
     */
    protected $domainUrl;

    /**
     * Router
     *
     * @var \Laminas\Router\Http\TreeRouteStack
     */
    protected $router;

    /**
     * Constructor
     *
     * @param SessionSettings $ss           Session settings
     * @param RecordLoader    $recordLoader Recordloader
     * @param string          $domainUrl    Domain url as string
     * @param FileLoader      $fileLoader   Fileloader
     * @param Router          $router       Router
     */
    public function __construct(
        SessionSettings $ss, RecordLoader $recordLoader,
        string $domainUrl, FileLoader $fileLoader, TreeRouteStack $router
    ) {
        $this->sessionSettings = $ss;
        $this->recordLoader = $recordLoader;
        $this->domainUrl = $domainUrl;
        $this->fileLoader = $fileLoader;
        $this->router = $router;
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

        $id = $params->fromPost('id', $params->fromQuery('id'));
        $index = $params->fromPost('index', $params->fromQuery('index'));
        $format = $params->fromPost('format', $params->fromQuery('format'));

        if (!$id || !$index || !$format) {
            return json_encode(['status' => self::STATUS_HTTP_BAD_REQUEST]);
        }
        $format = strtolower($format);
        $fileName = urlencode($id) . '-' . $index . '.' . $format;
        $driver = $this->recordLoader->load($id, 'Solr');
        $models = $driver->tryMethod('getModels');
        if (empty($models[$index][$format]['preview'])) {
            return $this->formatResponse(['json' => ['status' => '404']]);
        }
        // Always force preview model to be fetched
        $url = $models[$index][$format]['preview'];
        // Use fileloader for proxies
        $file = $this->fileLoader->getFile($url, $fileName, 'Models', 'public');
        $route = stripslashes($this->router->getBaseUrl());
        // Point url to public cache so viewer has access to it
        $url = "{$this->domainUrl}{$route}/cache/{$fileName}";
        return $this->formatResponse(compact('url'));
    }
}
