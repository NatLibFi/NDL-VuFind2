<?php

namespace Finna\AjaxHandler;

use VuFind\Cache\Manager as CacheManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Record\Loader;
use VuFind\Session\Settings as SessionSettings;
use Laminas\Config\Config;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use Laminas\Http\Request;
use Laminas\View\Helper\ServerUrl;

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
     * Cache manager
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * Loader
     * 
     * @var Loader
     */
    protected $loader;

    /**
     * Router
     * 
     * @var \Laminas\Router\Http\TreeRouteStack
     */
    protected $router;

    /**
     * Domain url
     * 
     * @var string
     */
    protected $domainUrl;
    /**
     * Constructor
     */
    public function __construct(
        SessionSettings $ss, CacheManager $cm, Config $config, Loader $loader,
        \Laminas\Router\Http\TreeRouteStack $router,
        string $domainUrl
    ) {
        $this->cacheManager = $cm;
        $this->sessionSettings = $ss;
        $this->config = $config;
        $this->recordLoader = $loader;
        $this->router = $router;
        $this->domainUrl = $domainUrl;
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
        $cacheDir = $this->cacheManager->getCache('public')->getOptions()
            ->getCacheDir();
        $fileName = urlencode($id) . '-' . $index . '.' . $format;
        $localFile = "$cacheDir/$fileName";
        $maxAge = $this->config->Content->modelCacheTime ?? 604800;
        // Check if the model has been cached
        if (!file_exists($localFile) && filemtime($localFile) < $maxAge * 60) {
            $driver = $this->recordLoader->load($id, 'Solr');
            $models = $driver->getModels();
            if (!isset($models[$index][$format])) {
                return $this->formatResponse(json_encode(['json' => ['status' => self::STATUS_HTTP_BAD_REQUEST]]));
            }
            $model = $models[$index][$format];

            $url = $model['url'];
            if (empty($url)) {
                return $this->formatResponse(['json' => ['status' => '404']]);
            }
            // Load the file from a server
            $file = file_get_contents($url);
            if (!$file) {
                return $this->formatResponse(['json' => ['status' => '500']]);
            }
            // Save the file to a local cache for future usage
            $save = file_put_contents($localFile, $file);
            if (!$save) {
                return $this->formatResponse(['json' => ['status' => '500']]);
            }
        }
        $route = stripslashes($this->router->getBaseUrl());
        // Point url to public cache so viewer can download it properly
        $url = "{$this->domainUrl}{$route}/cache/$fileName";

        return $this->formatResponse(['url' => $url]);
    }
}
