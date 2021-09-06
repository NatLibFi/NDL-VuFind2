<?php
/**
 * File Loader.
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
 * @package  File
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\File;

use Laminas\Config\Config;
use VuFind\Cache\Manager as CacheManager;

/**
 * File loader
 *
 * @category VuFind
 * @package  File
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Loader implements \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * Cache Manager
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Constructor
     *
     * @param CacheManager $cm     Cache Manager
     * @param Config       $config Config
     */
    public function __construct(CacheManager $cm, Config $config)
    {
        $this->cacheManager = $cm;
        $this->config = $config;
    }

    /**
     * Download a file to cache
     *
     * @param string $url           Url to download
     * @param string $fileName      Name of the file to save
     * @param string $configSection Section of the configFile to get cacheTime from
     * @param string $cacheFolder   What cache folder to use
     *
     * @return array
     */
    public function getFile(
        string $url, string $fileName,
        string $configSection, string $cacheFolder
    ): array {
        $cacheDir = $this->cacheManager->getCache($cacheFolder ?? 'public')
            ->getOptions()->getCacheDir();
        $path = "$cacheDir/$fileName";
        $maxAge = $this->config->$configSection->cacheTime ?? 43200;
        $result = true;
        $error = '';
        if (!file_exists($path) || time() - filemtime($path) > $maxAge * 60) {
            $client = $this->httpService->createClient(
                $url, \Laminas\Http\Request::METHOD_GET, 300
            );
            $client->setOptions(['useragent' => 'VuFind']);
            $client->setStream();
            $adapter = new \Laminas\Http\Client\Adapter\Curl();
            $client->setAdapter($adapter);
            $result = $client->send();

            if (!$result->isSuccess()) {
                $error = "Failed to retrieve file from $url";
                $this->debug($error);
                $result = false;
            } else {
                if ($fp = fopen($path, "w")) {
                    $result = stream_copy_to_stream($result->getStream(), $fp);
                    fclose($fp);
                } else {
                    $result = false;
                    $error = "Failed to open $path with for writing";
                    $this->debug($error);
                }
            }
        }

        return compact('result', 'path', 'error');
    }
}
