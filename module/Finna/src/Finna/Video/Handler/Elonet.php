<?php

/**
 * Elonet video module.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  Video
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\Video\Handler;

/**
 * Elonet video module.
 *
 * @category VuFind
 * @package  Video
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Elonet extends \Finna\Video\Handler\AbstractBase
{
    /**
     * Array of required configuration settings
     *
     * @var array
     */
    protected $required = ['url', 'secret', 'timestampPrecision'];

    /**
     * Convert url for Elonet video.
     *
     * @param string $url End part of the url to merge.
     *
     * @return string
     */
    protected function getURL(string $url): string
    {
        $now = time();
        // Round the timestamp to the wanted precision to allow CDN caching by not
        // generating unique URLs for every response.
        $timestamp = $now - $now % $this->config['timestampPrecision'] ?? 3000;
        $hashable = implode(':', [$url, (string)$timestamp]);
        $signature = hash_hmac('sha256', $hashable, $this->config['secret']);
        return sprintf(
            '%s%s?ts=%s&sig=%s',
            $this->config['url'],
            $url,
            $timestamp,
            $signature
        );
    }

    /**
     * Convert given array into array containing videos.
     *
     * @param array $data To convert.
     *
     * @return array
     */
    public function getData(array $data = []): array
    {
        $results = parent::getData($data);
        foreach ($data as $media) {
            if (empty($media['id'])) {
                continue;
            }
            $isChrome = stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false;
            $format = $isChrome ? 'mpd' : 'm3u8';
            $url = $this->getUrl("/playlist/{$media['id']}.$format");
            $posterUrl = $this->getUrl("/picture/{$media['id']}.1280.jpg");
            $results[] = [
                'url' => $url,
                'posterUrl' => $posterUrl,
                'description' => $media['description'] ?: $media['type'],
                'type' => $media['type'],
                'text' => $media['text'],
                'source' => $this->source,
                'embed' => 'video',
                'videoSources' => [
                    'src' => $url,
                    'type' => $format === 'mpd'
                        ? 'application/dash+xml'
                        : 'application/x-mpegURL',
                ],
            ];
        }
        return $results;
    }
}
