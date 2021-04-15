<?php

namespace Finna\File;

use Finna\File\LoaderFactory;

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
        string $filename): boolean
    {
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
}