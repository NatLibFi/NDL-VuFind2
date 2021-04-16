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
 * @package  Controller
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\File;

class Loader extends LoaderFactory
{
    public function __construct($config, \VuFindHttp\HttpService $httpService)
    {
        $this->config = $config;
        $this->httpService = $httpService;
    }

    public function getFileStreamed(
        string $url,
        string $contentType,
        string $filename
    ): bool {
        header("Content-Type: $contentType");
        header("Content-disposition: attachment; filename=\"{$filename}\"");
        $client = $this->httpService->createClient(
            $url, \Laminas\Http\Request::METHOD_GET, 300
        );
        $client->setOptions(['useragent' => 'VuFind']);
        $client->setStream();
        $adapter = new \Laminas\Http\Client\Adapter\Curl();
        $client->setAdapter($adapter);
        $adapter->setOptions(
            [
                'curloptions' => [
                    CURLOPT_WRITEFUNCTION => function ($ch, $str) {
                        echo $str;
                        return strlen($str);
                    }
                ]
            ]
        );
        $result = $client->send();

        if (!$result->isSuccess()) {
            $this->debug("Failed to retrieve file from $url");
            return false;
        }

        return true;
    }

    public function getFile(
        string $url,
        string $contentType,
        string $filename,
        string $path
    ): bool {
        header("Content-Type: $contentType");
        header("Content-disposition: attachment; filename=\"{$filename}\"");
        $client = $this->httpService->createClient(
            $url, \Laminas\Http\Request::METHOD_GET, 300
        );
        $client->setOptions(['useragent' => 'VuFind']);
        $client->setStream();
        $adapter = new \Laminas\Http\Client\Adapter\Curl();
        $client->setAdapter($adapter);
        $result = $client->send();

        if (!$result->isSuccess()) {
            $this->debug("Failed to retrieve file from $url");
            return false;
        }
        $fp = fopen($path, "w");
        stream_copy_to_stream($result->getStream(), $fp);

        return true;
    }
}