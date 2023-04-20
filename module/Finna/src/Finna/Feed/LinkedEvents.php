<?php

/**
 * Linked events service
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020-2023.
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
 * @package  Content
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\Feed;

use Finna\View\Helper\Root\CleanHtml;
use Laminas\Config\Config;
use Laminas\Mvc\Controller\Plugin\Url;
use VuFind\Cache\Manager as CacheManager;

/**
 * Linked events service
 *
 * @category VuFind
 * @package  Content
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class LinkedEvents implements
    \VuFindHttp\HttpServiceAwareInterface,
    \Laminas\Log\LoggerAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Api url
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Publisher ID
     *
     * @var string
     */
    protected $publisherId = '';

    /**
     * Language
     *
     * @var string
     */
    protected $language;

    /**
     * Date converter
     *
     * @var \VuFind\Date\Converter
     */
    protected $dateConverter;

    /**
     * Url helper
     *
     * @var Url
     */
    protected $url;

    /**
     * CleanHtml helper
     *
     * @var CleanHtml
     */
    protected $cleanHtml;

    /**
     * Cache manager
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Main configuration.
     *
     * @var Config
     */
    protected $mainConfig;

    /**
     * How many related events (if available) are displayed on
     * the events content page
     *
     * @var int
     */
    protected $relatedEventsAmount = 5;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $config        OrganisationInfo config
     * @param \VuFind\Date\Converter $dateConverter Date converter
     * @param Url                    $url           Url helper
     * @param CleanHtml              $cleanHtml     cleanHtml helper
     * @param CacheManager           $cm            Cache manager
     * @param Config                 $mainConfig    Main configuration
     */
    public function __construct(
        \Laminas\Config\Config $config,
        \VuFind\Date\Converter $dateConverter,
        Url $url,
        CleanHtml $cleanHtml,
        CacheManager $cm,
        Config $mainConfig
    ) {
        $this->apiUrl = $config->LinkedEvents->api_url ?? '';
        $this->publisherId = $config->LinkedEvents->publisher_id ?? '';
        $this->language = $config->General->language ?? '';
        $this->dateConverter = $dateConverter;
        $this->url = $url;
        $this->cleanHtml = $cleanHtml;
        $this->cacheManager = $cm;
        $this->mainConfig = $mainConfig;
    }

    /**
     * Return events from the LinkedEvents API
     *
     * @param array $params array of parameters. Key 'query' has API query
     *                      parameters as value, key 'url' has full URL as value.
     *                      If 'url' is provided, 'query' is ignored.
     *
     * @return array array of events
     */
    public function getEvents($params)
    {
        if (empty($this->apiUrl) || empty($this->publisherId)) {
            $this->logError('Missing LinkedEvents configuration');
            return false;
        }
        $paramArray = [];
        if (
            !empty($params['url'])
            && strncmp($params['url'], $this->apiUrl, strlen($this->apiUrl)) === 0
        ) {
            $url = $params['url'];
        } else {
            $paramArray = $params['query'];
            if (isset($paramArray['start'])) {
                $paramArray['start'] = $this->dateConverter->convert(
                    'd-m-Y',
                    'Y-m-d',
                    $paramArray['start']
                );
            } elseif (empty($paramArray['end'])) {
                $paramArray['start'] = date('Y-m-d');
            }
            if (isset($paramArray['end'])) {
                $paramArray['end'] = $this->dateConverter->convert(
                    'd-m-Y',
                    'Y-m-d',
                    $paramArray['end']
                );
            }
            if (isset($paramArray['language'])) {
                $map = ['en-gb' => 'en'];
                $this->language
                    = $map[$paramArray['language']] ?? $paramArray['language'];
            }

            $url = $this->apiUrl . 'event/';
            if (!empty($paramArray['id'])) {
                $url .= $paramArray['id'] . '/?include=location,audience,keywords,' .
                 'sub_events,super_event';
            } else {
                $url .= '?'
                . 'publisher=' . $this->publisherId . '&'
                . http_build_query($paramArray)
                . '&sort=start_time'
                . '&include=location';
            }
        }

        // Check for cached version
        $cacheDir
            = $this->cacheManager->getCache('feed')->getOptions()->getCacheDir();
        $localFile = "$cacheDir/" . md5(var_export($params, true)) . '.json';
        $maxAge = isset($this->mainConfig->Content->feedcachetime)
            && '' !== $this->mainConfig->Content->feedcachetime
            ? $this->mainConfig->Content->feedcachetime : 10;
        if (
            $maxAge && is_readable($localFile)
            && time() - filemtime($localFile) < $maxAge * 60
        ) {
            $response = json_decode(file_get_contents($localFile), true);
        } else {
            $client = $this->httpService->createClient($url);
            $client->setOptions(['useragent' => 'VuFind']);
            $result = $client->send();
            if (!$result->isSuccess()) {
                $this->logError('LinkedEvents API request failed, url: ' . $url);
                return false;
            }
            $body = $result->getBody();
            $response = json_decode($body, true);
            file_put_contents($localFile, $body);
        }
        $events = [];
        $result = [];
        if (!empty($response)) {
            $responseData = !isset($response['data'])
                ? [$response]
                : $response['data'];
            foreach ($responseData ?: [] as $eventData) {
                $link = $this->url->fromRoute('linked-events-content')
                    . '?id=' . $eventData['id'];

                $providerLink = $this->getField($eventData, 'provider_link');
                if (
                    $providerLink
                    && !preg_match('/^https?:\/\//', $providerLink)
                ) {
                    $providerLink = 'http://' . $providerLink;
                }

                $startDate = $this->formatDate($eventData['start_time']);
                $endDate = $this->formatDate($eventData['end_time']);
                $event = [
                    'id' => $eventData['id'],
                    'title' => $this->getField($eventData, 'name'),
                    'description' => ($this->cleanHtml)(
                        $this->getField($eventData, 'description')
                    ),
                    'image' => [
                        'url' => $this->proxifyImageUrl(
                            $eventData['images'][0]['url'] ?? '',
                            $params
                        ),
                    ],
                    'short_description' =>
                        $this->getField($eventData, 'short_description'),
                    'xcal' => [
                        'startTime' => $this->formatTime($eventData['start_time']),
                        'endTime' => $this->formatTime($eventData['end_time']),
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'singleDay' => $startDate === $endDate,
                        'location' =>
                            $this->getField($eventData, 'location_extra_info'),
                    ],
                    'info_url' => $this->getField($eventData, 'info_url'),
                    'location-info' =>
                        $this->getField($eventData, 'location_extra_info'),
                    'location' => $this->getField($eventData, 'location'),
                    'phone' => $this->getField($eventData, 'provider_phone'),
                    'email' => $this->getField($eventData, 'provider_email'),
                    'address' =>
                        $this->getField(
                            $eventData['location'],
                            'street_address'
                        ),
                    'price' => $this->getField($eventData, 'offers'),
                    'audience' => $this->getField($eventData, 'audience'),
                    'provider' => $this->getField($eventData, 'provider_name'),
                    'providerLink' => $providerLink,
                    'link' => $link,
                    'keywords' => $this->getField($eventData, 'keywords'),
                    'superEvent' => $eventData['super_event'],
                    'subEvents' => $eventData['sub_events'],
                ];

                $events[] = $event;
                if (
                    ($eventData['super_event'] !== null
                    || !empty($eventData['sub_events']))
                    && !empty($paramArray['id'])
                ) {
                    $superEventId
                        = $eventData['super_event']['id'] ?? $eventData['id'];
                    $newApiUrl = $this->apiUrl . 'event/?super_event='
                        . $superEventId . '&page_size='
                        . $this->relatedEventsAmount;
                    $relatedEvents = $this->getEvents(['url' => $newApiUrl]);
                    $events['relatedEvents'] = $relatedEvents['events'];
                }
            }
            if (isset($response['meta'])) {
                $result = [
                    'next' => $this->getField($response['meta'], 'next'),
                ];
            }
            $result['events'] = $events;
        }
        return $result;
    }

    /**
     * Return the value of the field in the configured language
     *
     * @param array  $object object
     * @param string $field  field
     *
     * @return string
     */
    public function getField($object, $field)
    {
        if (!isset($object[$field])) {
            return '';
        }
        $data = $object[$field];
        if ($field === 'offers' && !empty($data)) {
            if ($data[0]['is_free'] === true) {
                return null;
            } else {
                $data = $data[0]['price'];
            }
        }
        if ($field === 'audience' && !empty($data)) {
            $data = $data[0]['name'] ?? '';
        }

        if ($field === 'location') {
            $coordinates = [];
            if (isset($data['position']['coordinates'])) {
                $coordinates = [
                    'lng' => $data['position']['coordinates'][0],
                    'lat' => $data['position']['coordinates'][1],
                ];
            }
            return $coordinates;
        }
        if ($field === 'keywords' && !empty($data)) {
            $keywords = [];
            foreach ($data as $keyword) {
                $keywords[] = $this->getField($keyword, 'name');
            }
            return $keywords;
        }
        if (is_array($data)) {
            $data = !empty($data[$this->language])
                ? $data[$this->language]
                : ($data['fi'] ?? '');
        }
        return $data;
    }

    /**
     * Format date
     *
     * @param string $date Date to format
     *
     * @return string|null
     */
    public function formatDate($date)
    {
        if ($date) {
            return $this->dateConverter->convertToDisplayDate('Y-m-d', $date);
        }
        return null;
    }

    /**
     * Format time
     *
     * @param string $time Time to format
     *
     * @return string|null
     */
    public function formatTime($time)
    {
        if ($time) {
            return $this->dateConverter->convertToDisplayTime('Y-m-d', $time);
        }
        return null;
    }

    /**
     * Proxify an image url for loading via the FeedContent controller
     *
     * @param string $url    Image URL
     * @param array  $params Array of parameters
     *
     * @return string
     */
    public function proxifyImageUrl(string $url, array $params): string
    {
        // Ensure that we don't proxify an empty or already proxified URL:
        if (!$url) {
            return '';
        }
        $check = $this->url->fromRoute('linked-events-image', []);
        if (strncasecmp($url, $check, strlen($check)) === 0) {
            return $url;
        }

        $params['image'] = $url;
        return $this->url->fromRoute(
            'linked-events-image',
            [],
            [
                'query' => $params,
            ]
        );
    }
}
