<?php

/**
 * Record driver media helper.
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
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;
use VuFind\RecordDriver\AbstractBase as AbstractRecord;
use VuFind\View\Helper\Root\ClassBasedTemplateRendererTrait;

use function count;
use function get_class;
use function is_array;

/**
 * Record driver media helper.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class RecordMedia extends AbstractHelper
{
    use ClassBasedTemplateRendererTrait;

    /**
     * Record driver.
     *
     * @var ?AbstractRecord
     */
    protected ?AbstractRecord $driver = null;

    /**
     * Already processed URLs. Used to avoid duplicate URLs.
     *
     * @var array
     */
    protected array $renderedURLs = [];

    /**
     * All the cache keys to store urls in proper caches.
     *
     * @var array
     */
    protected array $cacheKeys = [
        'videoURLs',
        'audioURLs',
        'otherURLs',
        'onlineURLs',
        'mergedURLs',
        'iiifManifests',
    ];

    /**
     * Runtime cache.
     *
     * @var array
     */
    protected array $cache = [];

    /**
     * External link icon map.
     *
     * @var array
     */
    protected array $externalIconMap = [
        'Database Guide' => 'database-info',
        'Database Interface' => 'database-browse',
        'proxy-link' => 'download',
    ];

    /**
     * Invoke. Processes all urls for record and assigns them into proper caches.
     *
     * @param AbstractRecord $driver Record to process media
     *
     * @return self
     */
    public function __invoke(AbstractRecord $driver)
    {
        $this->resetCache();
        $this->driver = $driver;
        $this->processRecordMedia();
        return $this;
    }

    /**
     * Render template containing media for the record.
     *
     * @return string
     */
    public function renderMedia(): string
    {
        return $this->renderClassTemplate(
            'RecordDriver/%s/media.phtml',
            get_class($this->driver),
            $context ?? [
                'driver' => $this->driver,
                'videoURLs' => $this->cache['videoURLs'],
                'audioURLs' => $this->cache['audioURLs'],
                'iiifManifests' => $this->cache['iiifManifests'],
            ]
        );
    }

    /**
     * Render template containing urls for the record.
     *
     * @return string
     */
    public function renderURLs(): string
    {
        return $this->renderClassTemplate(
            'RecordDriver/%s/urls-container.phtml',
            get_class($this->driver),
            [
              'driver' => $this->driver,
              'otherURLs' => $this->cache['otherURLs'],
              'onlineURLs' => $this->cache['onlineURLs'],
              'mergedURLs' => $this->cache['mergedURLs'],
            ]
        );
    }

    /**
     * Process all the record media obtainable from a record and assign them into proper caches.
     * All the caches contains counts and urls.
     *
     * @return void
     */
    protected function processRecordMedia(): void
    {
        $openUrl = ($this->getView()->plugin('openUrl'))($this->driver, 'record');
        $openUrlActive = $openUrl->isActive();
        // Account for replace_other_urls setting
        $urls = ($this->getView()->plugin('record'))($this->driver)->getLinkDetails($openUrlActive);
        $driverOnlineURLs = $this->driver->tryMethod('getOnlineURLs', [['images']], []);
        $mergedData = $this->driver->tryMethod('getMergedRecordData', default: []);

        $iiifManifests = $this->driver->tryMethod('getIiifManifests', default: []);

        $this->cache['iiifManifests']['count'] = count($iiifManifests);
        $this->cache['iiifManifests']['urls'] = $iiifManifests;

        foreach ($driverOnlineURLs as $url) {
            $url = json_decode($url, true);
            $this->supplementURL($url);
            if ($this->deduplicateURL($url)) {
                continue;
            }
            $this->processURL($url, 'onlineURLs');
        }

        foreach ($urls as $url) {
            $this->supplementURL($url);
            if ($this->deduplicateURL($url)) {
                continue;
            }
            $this->processURL($url, 'otherURLs');
        }

        foreach ($mergedData['urls'] ?? [] as $url) {
            $this->supplementURL($url);
            if ($this->deduplicateURL($url)) {
                continue;
            }
            $this->processURL($url, 'mergedURLs');
        }
    }

    /**
     * Support function to supplement external urls with proper icon and description.
     *
     * @param array $url URL to process
     *
     * @return void
     */
    protected function supplementURL(&$url): void
    {
        $desc = $url['desc'] ?? $url['url'];
        if ($desc === $url['url']) {
            $desc = ($this->getView()->plugin('truncateUrl'))($url['url']);
        }

        if ($icon = ($this->externalIconMap[$desc] ?? '')) {
            $url['icon'] = $icon;
        }
        $url['desc'] = $desc;
    }

    /**
     * Deduplicates the url and fills the original url with any missing information.
     *
     * @param array $url URL to deduplicate
     *
     * @return bool If the URL was deduplicated to avoid displaying it more than once.
     */
    protected function deduplicateURL(array $url): bool
    {
        foreach ($this->renderedURLs as $renderedURL) {
            if ($url['url'] === $renderedURL['url']) {
                $cache = $renderedURL['cachedTo'];
                foreach ($this->cache[$cache]['urls'] as &$originalURL) {
                    if ($originalURL['url'] === $url['url']) {
                        foreach ($url as $key => $value) {
                            if (!isset($originalURL[$key])) {
                                $originalURL[$key] = $value;
                            }
                        }
                        if (is_array($url['source'] ?? '') && !is_array($originalURL['source'])) {
                            $originalURL['source'] = $url['source'];
                        }
                        break;
                    }
                }
                unset($originalURL);
                return true;
            }
        }
        return false;
    }

    /**
     * Process the given url and assign it to its correct cache.
     *
     * @param array  $url           URL to process
     * @param string $leftoverCache In which cache should the URL be placed if it is not a visual media.
     *
     * @return void
     */
    protected function processURL(array $url, string $leftoverCache): void
    {
        $cachedTo = $leftoverCache;
        $isEmbeddedVideo
            = $this->getView()->plugin('recordLinker')->getEmbeddedVideo($url['url']) === 'data-embed-iframe';

        if (($url['embed'] ?? '') === 'video' || !empty($url['videoSources']) || $isEmbeddedVideo) {
            $this->cache['videoURLs']['count']++;
            $this->cache['videoURLs']['urls'][] = $url;
            $cachedTo = 'videoURLs';
        } elseif (($url['embed'] ?? '') === 'audio') {
            $this->cache['audioURLs']['count']++;
            $this->cache['audioURLs']['urls'][] = $url;
            $cachedTo = 'audioURLs';
        } else {
            $this->cache[$leftoverCache]['count']++;
            $this->cache[$leftoverCache]['urls'][] = $url;
        }
        $this->renderedURLs[] = [
          'url' => $url['url'],
          'cachedTo' => $cachedTo,
        ];
    }

    /**
     * Reset all the caches so the counts and URLs are correct for each record.
     *
     * @return void
     */
    protected function resetCache(): void
    {
        foreach ($this->cacheKeys as $key) {
            $this->cache[$key]['count'] = 0;
            $this->cache[$key]['urls'] = [];
        }
    }
}
