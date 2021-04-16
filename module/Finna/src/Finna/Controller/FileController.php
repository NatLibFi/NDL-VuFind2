<?php

namespace Finna\Controller;

use VuFind\Cache\Manager as CacheManager;
use VuFind\Record\Loader as RecordLoader;
use VuFind\Session\Settings as SessionSettings;
use Finna\File\Loader as FileLoader;

class FileController extends \Laminas\Mvc\Controller\AbstractActionController
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Record loader
     * 
     * @var RecordLoader
     */
    protected $recordLoader;

    /**
     * File loader
     * 
     * @var FileLoader
     */
    protected $fileLoader;

    /**
     * Cache manager
     * 
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Session settings
     * 
     * @var SessionSettings
     */
    protected $sessionSettings;

    /**
     * Constructor
     */
    public function __construct(
        RecordLoader $recordLoader,
        FileLoader $fileLoader,
        CacheManager $cm,
        SessionSettings $ss
    ) {
        $this->recordLoader = $recordLoader;
        $this->fileLoader = $fileLoader;
        $this->cacheManager = $cm;
        $this->sessionSettings = $ss;
    }

    /**
     * Download 3d model
     */
    public function downloadModelAction()
    {
        $this->sessionSettings->disableWrite(); // avoid session write timing bug
        $params = $this->params();
        $id = $params->fromQuery('id');
        $index = $params->fromQuery('index');
        $format = $params->fromQuery('format', '');
        $type = $params->fromQuery('type');
        $response = $this->getResponse();

        if ($id && $index && $type) {
            $driver = $this->recordLoader->load(
                $id, $params->fromQuery('source') ?? DEFAULT_SEARCH_BACKEND
            );
            $filename = urlencode($id) . '-' . $index . '.' . $format;
            $models = $driver->tryMethod('getModels');
            $url = $models[$index][$format][$type] ?? false;
            if (!empty($url)) {
                $contentType = '';
                switch ($format) {
                case 'gltf':
                    $contentType = 'model/gltf+json';
                    break;
                case 'glb':
                    $contentType = 'model/gltf+binary';
                    break;
                default:
                    $contentType = 'application/octet-stream';
                    break;
                }
                $res = $this->fileLoader->getFileStreamed($url, $contentType, $filename);
                if (!$res) {
                    $response->setStatusCode(500);
                }
            } else {
                $response->setStatusCode(404);
            }
        } else {
            $response->setStatusCode(400);
        }

        return $response;
    }
}