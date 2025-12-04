<?php

/**
 * IIIF manifest generator service
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * @package  Content
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Record\IIIF;

use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;
use VuFind\RecordDriver\AbstractBase as RecordDriver;
use VuFind\View\Helper\Root\RecordLinker;

/**
 * IIIF manifest generator service
 *
 * Only intended for internal use as a compatibility layer. With this we can use
 * Tify to show non-IIIF images and image sets.
 *
 * @category VuFind
 * @package  Content
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class IIIFManifestGenerator implements
    \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Constructor.
     *
     * @param Url          $url          URL helper
     * @param ServerUrl    $serverUrl    Server URL helper
     * @param RecordLinker $recordLinker RecordLinker helper
     *                                   For getting the URL of the record
     *                                   action constructing this class
     */
    public function __construct(
        protected Url $url,
        protected ServerUrl $serverUrl,
        protected RecordLinker $recordLinker,
    ) {
    }

    /**
     * Generate IIIF presentation manifest (version 3)
     *
     * @param RecordDriver $driver Record driver
     *
     * @return array|null
     */
    public function generate(RecordDriver $driver): array|null
    {
        $images = $driver->tryMethod('getAllImages');
        if (!$images) {
            return null;
        }

        $recordId = $driver->getUniqueID();
        $manifestId = ($this->serverUrl)(
            $this->recordLinker->getActionUrl(
                $driver,
                'IIIFManifest',
                options: ['force_canonical' => true]
            )
        );

        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => $manifestId,
            'type' => 'Manifest',
            'thumbnail' => [],
            'metadata' => [],
            'items' => [],
        ];

        foreach ($images as $idx => $image) {
            $canvasItem = [
                'id' => "$manifestId/$idx",
                'type' => 'Canvas',
                'items' => [],
            ];
            foreach (['large', 'medium', 'small'] as $size) {
                if (isset($image['urls'][$size])) {
                    $canvasItem['items'][] =
                        $this->createAnnotationPage(
                            $recordId,
                            $idx,
                            $size,
                            $driver,
                            $manifestId
                        );
                    break; // only take the largest $size
                }
            }
            $manifest['items'][] = $canvasItem;
        }

        if (empty($manifest['items'])) {
            return null;
        } else {
            return $manifest;
        }
    }

    /**
     * Creates annotation page representing a given image
     *
     * @param string       $recordId   Record unique ID
     * @param int          $index      Image number
     * @param string       $size       Image size: 'large', 'medium', 'small'
     * @param RecordDriver $driver     Record driver
     * @param string       $manifestId Manifest ID, i.e. URI to the calling
     *                                 RecordController action
     *
     * @return array
     */
    private function createAnnotationPage(
        string $recordId,
        int $index,
        string $size,
        RecordDriver $driver,
        string $manifestId
    ): array {
        $bodyId = ($this->url)(
            'cover-show',
            [],
            ['force_canonical' => true]
        ) . '?' . http_build_query([
            'id' => $recordId,
            'index' => $index,
            'size' => $size,
            'source' => $driver->getSourceIdentifier(),
        ]);
        $annotationPage = [
            'id' => "$manifestId/$index/$size",
            'type' => 'AnnotationPage',
            'items' => [[
                'id' => "$manifestId/$index/$size/1",
                'type' => 'Annotation',
                'motivation' => 'painting',
                'body' => [
                    'id' => $bodyId,
                    // NOTE: The image is served through the Cover/Show
                    // endpoint, which, as of 2025-12-12, forces a conversion to
                    // JPEG
                    'format' => 'image/jpeg',
                    'type' => 'Image',
                ],
                'target' => "$manifestId/$index",
            ]],
        ];

        return $annotationPage;
    }
}
