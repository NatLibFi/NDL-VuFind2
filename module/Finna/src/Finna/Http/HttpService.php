<?php

/**
 * VuFind HTTP service class file.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Http
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
namespace Finna\Http;

/**
 * VuFind HTTP service.
 *
 * @category VuFind
 * @package  Http
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
class HttpService extends \VuFindHttp\HttpService
{
    /**
     * Return a new HTTP client.
     *
     * @param string $url     Target URL
     * @param string $method  Request method
     * @param float  $timeout Request timeout in seconds
     *
     * @return \Laminas\Http\Client
     */
    public function createClient($url = null,
        $method = \Laminas\Http\Request::METHOD_GET, $timeout = null
    ) {
        $client = new Client();
        $client->setMethod($method);
        if (!empty($this->defaults)) {
            $client->setOptions($this->defaults);
        }
        if (null !== $this->defaultAdapter) {
            $client->setAdapter($this->defaultAdapter);
        }
        if (null !== $url) {
            $client->setUri($url);
        }
        if ($timeout) {
            $client->setOptions(['timeout' => $timeout]);
        }
        $this->proxify($client);
        return $client;
    }

    /// Internal API

    /**
     * Return query string based on params.
     *
     * @param array $params Parameters
     *
     * @return string
     */
    protected function createQueryString(array $params = [])
    {
        if ($this->isAssocParams($params)) {
            return http_build_query($params);
        } else {
            return implode('&', $params);
        }
    }

    /**
     * Send HTTP request and return response.
     *
     * @param \Laminas\Http\Client $client HTTP client to use
     *
     * @throws Exception\RuntimeException
     * @return \Laminas\Http\Response
     *
     * @todo Catch more exceptions, maybe?
     */
    protected function send(\Laminas\Http\Client $client)
    {
        try {
            $response = $client->send();
        } catch (\Laminas\Http\Client\Exception\RuntimeException $e) {
            throw new Exception\RuntimeException(
                sprintf('Laminas HTTP Client exception: %s', $e),
                -1,
                $e
            );
        }
        return $response;
    }

    /**
     * Return TRUE if argument is an associative array.
     *
     * @param array $array Array to test
     *
     * @return boolean
     */
    public static function isAssocParams(array $array)
    {
        foreach (array_keys($array) as $key) {
            if (!is_numeric($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return TRUE if argument refers to localhost.
     *
     * @param string $host Host to check
     *
     * @return boolean
     */
    protected function isLocal($host)
    {
        return preg_match($this->localAddressesRegEx, $host);
    }
}
