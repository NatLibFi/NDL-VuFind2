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

use Finna\View\Helper\Root\RecordLinker;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;
use VuFind\RecordDriver\AbstractBase as RecordDriver;

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
        $images      = $driver->tryMethod('getAllImages');
        $recordId    = $driver->getUniqueID();
        $source      = $driver->getSourceIdentifier();
        $manifestId  = $this->recordLinker->getGeneratedIiifManifestUrl($driver);
        $recordTitle = $driver->getTitle();

        return $this->createManifest(
            $images,
            $recordId,
            $source,
            $manifestId,
            $recordTitle
        );
    }

    /**
     * Handle actual manifest generation
     *
     * @param ?array $images      Images
     *                            Array, or null if driver did not have the
     *                            getAllImages method.
     * @param string $recordId    Unique ID of the record
     * @param string $source      Driver source, e.g. 'Solr'
     * @param string $manifestId  Manifest ID
     *                            The fully qualified URL of the IIIFManifest
     *                            action on RecordController. Must resolve
     *                            correctly.
     * @param string $recordTitle Title of the record
     *
     * @return array
     */
    protected function createManifest(
        ?array $images,
        string $recordId,
        string $source,
        string $manifestId,
        string $recordTitle
    ): ?array {
        if (!$images) {
            return null;
        }

        $manifestItems = [];

        foreach ($images as $idx => $image) {
            $canvasItem = [
                'id' => "$manifestId/$idx",
                'type' => 'Canvas',
                'metadata' => $this->createCanvasMetadata($image),
                'items' => [],
            ];

            if (
                $rightsLink = isset($image['rights']['link']) ?
                preg_replace('/\/[^\/]*$/', '/', $image['rights']['link']) :
                null
            ) {
                $canvasItem['rights'] = $rightsLink;
            }

            foreach (['large', 'medium', 'small'] as $size) {
                if (isset($image['urls'][$size])) {
                    $bodyId = $this->createBodyId(
                        $recordId,
                        $idx,
                        $size,
                        $source,
                    );
                    $canvasItem['items'][] =
                        $this->createAnnotationPage(
                            $idx,
                            $size,
                            $bodyId,
                            $manifestId,
                        );
                    break; // only take the largest $size
                }
            }
            $manifestItems[] = $canvasItem;
        }

        if (!$manifestItems) {
            return null;
        }

        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => $manifestId,
            'type' => 'Manifest',
            'thumbnail' => [],
            'label' => [
                'fi' => $recordTitle,
                'sv' => $recordTitle,
                'en' => $recordTitle,
                'se' => $recordTitle,
            ],
            'metadata' => [],
            'items' => $manifestItems,
        ];

        return $manifest;
    }

    /**
     * Create metadata array for a canvas
     *
     * @param array $image Image
     *
     * @return array
     */
    protected function createCanvasMetadata(array $image): array
    {
        $metadata = [];
        if (isset($image['description'])) {
            $metadata[] = [
                'label' => [
                    'en' => 'Description',
                    'fi' => 'Kuvaus',
                    'sv' => 'Beskrivning',
                    'se' => 'Govvádus',
                ],
                'value' => [
                    'en' => $image['description'],
                    'fi' => $image['description'],
                    'sv' => $image['description'],
                    'se' => $image['description'],
                ],
            ];
        }
        return $metadata;
    }

    /**
     * Build the cover URL for this image
     *
     * @param string $recordId Record unique ID
     * @param int    $index    Image number
     * @param string $size     Image size: 'large', 'medium', 'small'
     * @param string $source   Record source (e.g. 'Solr')
     *
     * @return string
     */
    protected function createBodyId(
        string $recordId,
        int $index,
        string $size,
        string $source
    ): string {
        return ($this->url)(
            'cover-show',
            [],
            [
                'force_canonical' => true,
                'query' => [
                    'id'     => $recordId,
                    'index'  => $index,
                    'size'   => $size,
                    'source' => $source,
                ],
            ]
        );
    }

    /**
     * Create annotation page representing a given image
     *
     * @param int    $index      Image number
     * @param string $size       Image size: 'large', 'medium', 'small'
     * @param string $bodyId     Cover URL of the image
     * @param string $manifestId Manifest ID, i.e. URI to the calling
     *                           RecordController action
     *
     * @return array
     */
    protected function createAnnotationPage(
        int $index,
        string $size,
        string $bodyId,
        string $manifestId
    ): array {
        $annotationPageId = "$manifestId/$index/$size";
        $annotationPage = [
            'id' => $annotationPageId,
            'type' => 'AnnotationPage',
            'items' => [[
                'id' => "$annotationPageId/1",
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
