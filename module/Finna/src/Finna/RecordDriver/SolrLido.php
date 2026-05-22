<?php

/**
 * Model for LIDO records in Solr.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2026.
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
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver;

use FinnaXml\XmlDoc;
use VuFind\I18n\TranslatableString;

use function boolval;
use function call_user_func_array;
use function count;
use function in_array;
use function intval;
use function is_array;
use function strlen;

/**
 * Model for LIDO records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrLido extends SolrDefault implements \Psr\Log\LoggerAwareInterface
{
    use Feature\FinnaXmlReaderTrait;
    use Feature\FinnaUrlCheckTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * LIDO XML namespace.
     *
     * @var string
     */
    public const LIDO_NAMESPACE = 'http://www.lido-schema.org';

    /**
     * Map from site locale to Lido language codes.
     */
    public const LANGUAGE_CODES = [
        'fi' => ['fi','fin'],
        'sv' => ['sv','swe'],
        'en-gb' => ['en','eng'],
    ];

    /**
     * Image types array.
     *
     * @var array
     */
    protected $imageTypes = [
        'image_thumb' => 'small',
        'thumb' => 'small',
        'medium' => 'medium',
        'image_large' => 'large',
        'large' => 'large',
        'zoomview' => 'large',
        'image_master' => 'master',
        'image_original' => 'original',
    ];

    /**
     * Model types array.
     *
     * @var array
     */
    protected $modelTypes = [
        'preview_3D' => 'preview',
        'provided_3D' => 'provided',
    ];

    /**
     * Audio types array.
     *
     * @var array
     */
    protected $audioTypes = [
        'preview_audio' => 'audio',
    ];

    /**
     * Video types array.
     *
     * @var array
     */
    protected $videoTypes = [
        'preview_video' => 'video',
    ];

    /**
     * Document types array.
     *
     * @var array
     */
    protected $documentTypes = [
        'preview_text' => 'document',
        'provided_text' => 'document',
    ];

    /**
     * Supported audio formats.
     *
     * @var array
     */
    protected $supportedAudioFormats = [
        'mp3' => 'mpeg',
        'wav' => 'wav',
    ];

    /**
     * Supported video formats.
     *
     * @var array
     */
    protected $supportedVideoFormats = [
        'mp4' => 'video/mp4',
        'video/mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'video/quicktime' => 'video/quicktime',
        'text/html' => 'text/html',
    ];

    /**
     * Description type mappings.
     *
     * @var array
     */
    protected $descriptionTypeMappings = [
        'preview_video' => 'displayLink',
        'preview_audio' => 'displayLink',
        'preview_text' => 'displayLink',
        'provided_text' => 'displayLink',
        'provided_video' => 'displayLink',
    ];

    /**
     * Measurement type mappings.
     *
     * @var array
     */
    protected $measurementTypeMappings = [
        'leveys' => 'width',
        'korkeus' => 'height',
        'koko' => 'size',
    ];

    /**
     * Measurement unit mappings.
     *
     * @var array
     */
    protected $measurementUnitMappings = [
        'tavua' => 'byte',
        'bytes' => 'byte',
        'pikseli' => 'pixel',
    ];

    /**
     * PlaceID source mappings.
     *
     * @var array
     */
    protected $placeIDSourceMappings = [
        'vtj' => 'prt',
        'kiinteistörekisteri' => 'kiinteistötunnus',
    ];

    /**
     * Inscription type mappings.
     *
     * @var array
     */
    protected $inscriptionTypeMappings = [
        'merkinnän sijainti' => 'location',
        'merkintä' => 'description',
        'merkintätekniikka' => 'technique',
        'sijainti' => 'location',
        'tekniikka' => 'technique',
        'tulkinta' => 'interpretation',
        'tyyppi' => 'type',
        'merkinnän tyyppi' => 'type',
    ];

    /**
     * Array of preferred title labels.
     *
     * @var array
     */
    protected $preferredTitleLabels = ['preferred', 'http://terminology.lido-schema.org/lido00169'];

    /**
     * Array of alternative title labels.
     *
     * @var array
     */
    protected $alternativeTitleLabels = ['alternate', 'alternative', 'http://terminology.lido-schema.org/lido00170'];

    /**
     * Array of web friendly model formats.
     *
     * @var array
     */
    protected $displayableModelFormats = ['gltf', 'glb'];

    /**
     * Array of excluded classifications.
     *
     * @var array
     */
    protected $excludedClassifications = ['language'];

    /**
     * Array of excluded measurements.
     *
     * @var array
     */
    protected $excludedMeasurements = ['extent'];

    /**
     * Subject conceptID types included in topic identifiers (all lowercase).
     *
     * @var array
     */
    protected $subjectConceptIDTypes = ['uri', 'url'];

    /**
     * PlaceID types included but not prepended to identifier (all lowercase).
     *
     * @var array
     */
    protected $uniquePlaceIDTypes = ['uri', 'url'];

    /**
     * Array of excluded subject types.
     *
     * @var array
     */
    protected $excludedSubjectTypes = ['aihe', 'iconclass'];

    /**
     * Array of types for linkResources to be displayed as external URL.
     */
    protected $displayExternalLinks = ['provided_3D'];

    /**
     * Array of types displayed as download links with documents.
     *
     * @var array
     */
    protected $displayDownloadLinks = ['provided_video'];

    /**
     * Array of related work relation types for related publications.
     *
     * @var array
     */
    protected $relatedPulicationRelationTypes = ['is reproduced in', 'kirjallisuus', 'lähteet', 'julkaisu'];

    /**
     * Array of related publication title labels excluded from search.
     *
     * @var array
     */
    protected $relatedPulicationTitlesExcludedFromSearch = ['verkkojulkaisu'];

    /**
     * Events used for author information.
     *
     * Key is event type, value is priority (lower is more important),
     *
     * @var array
     */
    protected $authorEvents = [
        'suunnittelu' => 1,
        'valmistus' => 2,
    ];

    /**
     * Events excluded from subjects.
     *
     * @var array
     */
    protected $nonPlaceEvents = [
        'valmistus',
        'näyttely',
    ];

    /**
     * Mapping from related work type to possible type attributes.
     *
     * @var array
     */
    protected $relatedWorkTypeMap = [
        'archive' => ['archive', 'arkisto', 'henkilöarkisto', 'yhteisöarkisto'],
        'collection' => ['collection', 'kokoelma', 'purchase batch', 'hankintaerä'],
        'subcollection' => ['subcollection', 'alakokoelma'],
        'series' => ['series', 'sarja', 'hankintaerä (lapsi)'],
        'work' => ['work', 'teos'],
    ];

    /**
     * Return access restriction notes for the record.
     *
     * @param string $language Optional primary language to look for
     *
     * @return array
     */
    public function getAccessRestrictions($language = '')
    {
        $restrictions = [];
        $reader = $this->getXmlReader();
        $rights = $reader->all(path: 'lido/administrativeMetadata/resourceWrap/resourceSet/rightsResource/rightsType');
        foreach ($rights as $right) {
            if (!$reader->first($right, 'conceptID')) {
                continue;
            }
            if ($term = $this->getLanguageSpecificValueByPath($right, 'term', $language)) {
                $restrictions[] = $term;
            }
        }
        return array_unique($restrictions);
    }

    /**
     * Return type of access restriction for the record.
     *
     * @param string $language Language
     *
     * @return mixed array with keys:
     *   'copyright'   Copyright (e.g. 'CC BY 4.0')
     *   'link'        Link to copyright info, see IndexRecord::getRightsLink
     *   or false if no access restriction type is defined.
     */
    public function getAccessRestrictionsType($language)
    {
        $reader = $this->getXmlReader();
        $path = 'lido/administrativeMetadata/resourceWrap/resourceSet/rightsResource/rightsType/conceptID';
        foreach ($reader->all(path: $path) as $conceptID) {
            if ($copyright = $reader->value($conceptID)) {
                $copyright = $this->getMappedRights($copyright);
                $data = compact('copyright');

                if ($link = $this->getRightsLink($copyright, $language)) {
                    $data['link'] = $link;
                }
                return $data;
            }
        }
        return false;
    }

    /**
     * Return an array of image URLs associated with this record with keys:
     * - urls        Image URLs
     *   - small     Small image (mandatory)
     *   - medium    Medium image (mandatory)
     *   - large     Large image (optional)
     * - description Description text
     * - rights      Rights
     *   - copyright   Copyright (e.g. 'CC BY 4.0') (optional)
     *   - description Human readable description (array)
     *   - link        Link to copyright info
     *   - rightsHolders Rights holders
     *   - creditLine  Credit line
     * - identifier  Image identifier
     * - type        Image type
     * - relationTypes Image relationships with the object
     * - dateTaken   Photo date taken
     * - perspectives Image perspectives
     *
     * @param string $language   Language for textual information
     * @param bool   $includePdf Whether to include first PDF file when no image
     *
     * @return array
     */
    public function getAllImages($language = null, $includePdf = false)
    {
        $language ??= $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        // Note: Do not reindex the results e.g. with array_values, the keys are important! See FINNA-3933 and
        // RecordImage::mergeModelDataToImages.
        return array_filter(array_column($representations, 'images'));
    }

    /**
     * Function to format given resourceMeasurementsSet to readable format.
     *
     * @param array[] $measurements Measurements nodes
     *
     * @return array
     */
    protected function formatResourceMeasurements(array $measurements): array
    {
        $reader = $this->getXmlReader();
        $results = [];
        foreach ($measurements as $set) {
            if (!($value = $reader->firstValue($set, 'measurementValue'))) {
                continue;
            }
            // Support both simple text and term element in measurementType and measurementUnit
            $type = '';
            if ($t = $reader->first($set, 'measurementType')) {
                $type = $reader->firstValue($t, 'term') ?? $reader->value($t);
                $type = $this->measurementTypeMappings[$type] ?? $type;
            }
            $unit = '';
            if ($u = $reader->first($set, 'measurementUnit')) {
                $unit = $reader->firstValue($u, 'term') ?? $reader->value($u);
                $unit = $this->measurementUnitMappings[$unit] ?? $unit;
            }
            // The museumplus cannot handle image sizes with multiple layers
            // so explode the results and sum them if the value is too long.
            // Example of the value to be summed: 400030 400030 21313 223314.
            // Only do this with sizes.
            if ($type === 'size') {
                if (strlen($value) > 15) {
                    $tmpValue = 0;
                    foreach (explode(' ', $value) as $part) {
                        $tmpValue += (int)$part;
                    }
                    $value = $tmpValue;
                }
                $value = intval(preg_replace('/[^0-9]/', '', $value));
            }
            $results[$type] = [
                'unit' => $unit,
                'value' => $value,
            ];
        }
        return $results;
    }

    /**
     * Get 3D models.
     *
     * @return array
     */
    public function getModels(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        // Note: Do not reindex the results e.g. with array_values, the keys are important! See FINNA-3933 and
        // RecordImage::mergeModelDataToImages.
        return array_filter(array_column($representations, 'models'));
    }

    /**
     * Get audios.
     *
     * @return array
     */
    protected function getAudios(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_values(
            array_filter(array_column($representations, 'audios'))
        );
    }

    /**
     * Get videos.
     *
     * @return array
     */
    protected function getVideos(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_values(
            array_filter(array_column($representations, 'videos'))
        );
    }

    /**
     * Get documents.
     *
     * @return array
     */
    public function getDocuments(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_values(
            array_filter(array_column($representations, 'documents'))
        );
    }

    /**
     * Parse given representations and return them in proper
     * associative array.
     *
     * @param string $language language to get information
     *
     * @return array
     */
    protected function getRepresentations(string $language): array
    {
        $cacheKey = __FUNCTION__ . "/$language";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $defaultRights = $this->getImageRights($language, true);

        $imageTypeKeys = array_keys($this->imageTypes);
        $modelTypeKeys = array_keys($this->modelTypes);
        $audioTypeKeys = array_keys($this->audioTypes);
        $videoTypeKeys = array_keys($this->videoTypes);
        $documentTypeKeys = array_keys($this->documentTypes);

        $results = [];
        $addToResults = function (
            array $images = [],
            array $models = [],
            array $audios = [],
            array $videos = [],
            array $documents = []
        ) use (&$results): void {
            if ($images) {
                if (!$this->maxAmountOfImages()) {
                    $images = $this->ensureImageSizes($images);
                    $images['downloadable'] = $this->allowRecordImageDownload($images);
                } else {
                    $images = [];
                }
                $this->imagesCount++;
            }
            $results[] = compact(
                'images',
                'models',
                'audios',
                'videos',
                'documents'
            );
        };

        $reader = $this->getXmlReader();
        foreach ($reader->all(path: 'lido/administrativeMetadata/resourceWrap/resourceSet') as $resourceSet) {
            // Process rights first since we may need to duplicate them if there
            // are multiple representations in the set (non-standard)
            if (!($rights = $this->getResourceRights($resourceSet, $language))) {
                $rights = $defaultRights;
            }

            $imageUrls = [];
            $modelUrls = [];
            $audioUrls = [];
            $videoUrls = [];
            $documentUrls = [];
            $highResolution = [];

            $descriptions = $this->getResourceDescriptions($resourceSet, $language);
            foreach ($reader->all($resourceSet, 'resourceRepresentation') as $representation) {
                if (!$linkResource = $reader->first($representation, 'linkResource')) {
                    continue;
                }
                $url = $reader->value($linkResource);
                if (!$url || !$this->isUrlLoadable($url, $this->getUniqueID())) {
                    continue;
                }
                $format = $reader->attr($linkResource, 'formatResource') ?? '';

                // Representation without a type is handled as a single image
                if (!($type = $reader->attr($representation, 'type') ?? '')) {
                    // We already have URL's, store them in the
                    // final results first. This shouldn't
                    // happen unless there are multiple
                    // images without type in the same set.
                    if ($imageUrls) {
                        $addToResults(
                            [
                                'urls' => $imageUrls,
                                'description' => '',
                                'rights' => $rights,
                            ]
                        );
                        $imageUrls = [];
                    }
                    $imageUrls['large'] = $url;
                    continue;
                }

                // If there is a description set for the representation
                // Try to find one with correct language, else get the first
                $description = '';
                if ($key = $this->descriptionTypeMappings[$type] ?? '') {
                    if ($foundDescriptions = $descriptions[$key] ?? []) {
                        $description = $foundDescriptions[$language] ?? reset($foundDescriptions);
                    }
                }
                // Representation is a document or wanted to be displayed also as an document
                $displayAsLink = in_array($type, $this->displayExternalLinks);
                if (
                    in_array($type, $documentTypeKeys)
                    || in_array($type, $this->displayDownloadLinks)
                    || $displayAsLink
                ) {
                    $documentDesc = $description;
                    $linkType = $displayAsLink ? 'external-link' : 'proxy-link';
                    // Override default linkType in datasource settings
                    $linkTypePreference =
                        $this->datasourceSettings[$this->getDataSource()]['display_preferences'][$type . '_link_type']
                        ?? '';
                    if (in_array($linkTypePreference, ['external-link', 'download', 'proxy-link'])) {
                        $linkType = $linkTypePreference;
                    }
                    if ($displayAsLink && !$documentDesc) {
                        $host = $this->safeParseUrl($url, PHP_URL_HOST);
                        $documentDesc = new TranslatableString(
                            "external_$host",
                            $host
                        );
                    }
                    $documentRights = $this->getResourceRights($resourceSet, $language, false);
                    if (
                        $document = $this->getDocument(
                            $url,
                            $format,
                            $documentDesc,
                            $documentRights,
                            $linkType,
                            $type,
                        )
                    ) {
                        $documentUrls = array_merge($documentUrls, $document);
                    }
                }
                // Representation is an image
                if (in_array($type, $imageTypeKeys)) {
                    $image = $this->getImage(
                        $url,
                        $type,
                        $language,
                        $reader->firstValue($resourceSet, 'resourceID') ?? '',
                        $format,
                        $reader->all($representation, 'resourceMeasurementsSet')
                    );
                    if ($image) {
                        if (!empty($image['displayImage'])) {
                            $imageUrls
                                = array_merge($imageUrls, $image['displayImage']);
                        }
                        // Does image have a highresolution for download?
                        if (!empty($image['highResolution'])) {
                            $highResolution = array_merge(
                                $highResolution,
                                $image['highResolution']
                            );
                        }
                    }
                    continue;
                }

                // Representation is a 3d model
                if (in_array($type, $modelTypeKeys)) {
                    if (
                        $model = $this->getModel(
                            $url,
                            $format,
                            $type,
                            $reader->all($representation, 'resourceMeasurementsSet')
                        )
                    ) {
                        $modelUrls[] = $model;
                    }
                    continue;
                }
                // Representation is an audio
                if (in_array($type, $audioTypeKeys)) {
                    if (
                        $audio = $this->getAudio(
                            $url,
                            $format,
                            $description,
                            $reader->all($representation, 'resourceMeasurementsSet')
                        )
                    ) {
                        $audioUrls = array_merge($audioUrls, $audio);
                        if ($extraDetails = $this->getExtraDetails($resourceSet, $language)) {
                            $audioUrls = array_merge($audioUrls, $extraDetails);
                        }
                    }
                    continue;
                }
                // Representation is a video
                if (in_array($type, $videoTypeKeys)) {
                    $videoRights = $this->getResourceRights($resourceSet, $language, false);
                    if (
                        $video = $this->getVideo(
                            $url,
                            $format,
                            $description,
                            $videoRights,
                            $reader->all($representation, 'resourceMeasurementsSet')
                        )
                    ) {
                        $videoUrls = array_merge($videoUrls, $video);
                        if ($extraDetails = $this->getExtraDetails($resourceSet, $language)) {
                            $videoUrls = array_merge($videoUrls, $extraDetails);
                        }
                    }
                    continue;
                }
            }
            // Save all the found results here as a new object
            // If current set has no links, continue to next one
            if (!$imageUrls && !$modelUrls && !$audioUrls && !$videoUrls && !$documentUrls) {
                continue;
            }
            $imageResult = [];
            // Process found image urls
            if ($imageUrls) {
                $imageResult = [
                    'urls' => $imageUrls,
                    'description' => '',
                    'rights' => $rights,
                    'highResolution' => $highResolution,
                ];

                if ($extraDetails = $this->getExtraDetails($resourceSet, $language)) {
                    $imageResult = array_merge($imageResult, $extraDetails);
                }
            }
            $modelResult = [];
            if ($modelUrls) {
                $modelResult = [
                    'models' => $modelUrls,
                    'rights' => $rights,
                ];
            }
            $addToResults(
                $imageResult,
                $modelResult,
                $audioUrls,
                $videoUrls,
                $documentUrls
            );
        }

        $this->cache[$cacheKey] = $results;
        return $results;
    }

    /**
     * Return description as associative array
     * - type Type of the description and text as the value.
     *
     * @param array  $resourceSet Set to get description from
     * @param string $language    Language to get
     *
     * @return array
     */
    protected function getResourceDescriptions(
        array $resourceSet,
        string $language
    ): array {
        // TODO: Why is $language not used?
        $results = [];
        $reader = $this->getXmlReader();
        foreach ($reader->all($resourceSet, 'resourceDescription') as $description) {
            if ($type = $reader->attr($description, 'type')) {
                if ($lang = $reader->attr($description, 'lang')) {
                    $results[$type][$lang] = $reader->value($description);
                } else {
                    $results[$type][] = $reader->value($description);
                }
            }
        }
        return $results;
    }

    /**
     * Returns associative array for images extra details
     * - identifier    resourceset id
     * - type          language specific type
     * - relationTypes language specific relation types
     * - description   language specific description
     * - dateTaken     date taken
     * - perspectives  language specific perspectives.
     *
     * @param array  $resourceSet Current resource set
     * @param string $language    Language to information
     *
     * @return array
     */
    protected function getExtraDetails(
        array $resourceSet,
        string $language
    ): array {
        $result = [];
        $reader = $this->getXmlReader();
        if ($resourceID = $reader->firstValue($resourceSet, 'resourceID')) {
            $result['identifier'] = $resourceID;
        }
        if ($term = $this->getLanguageSpecificValueByPath($resourceSet, 'resourceType/term', $language)) {
            $result['type'] = $term;
        }
        foreach ($reader->all($resourceSet, 'resourceRelType') as $relType) {
            if ($term = $this->getLanguageSpecificValueByPath($relType, 'term', $language)) {
                $result['relationTypes'][] = $term;
            }
        }
        if ($resourceDescription = $reader->all($resourceSet, 'resourceDescription')) {
            $description = $this->getLanguageSpecificNode($resourceDescription, $language);
            if ($descriptionValue = $reader->value($description)) {
                $type = $reader->attr($description, 'type');
                if ($type === 'displayLink') {
                    $result['resourceName'] = $descriptionValue;
                } else {
                    $result['resourceDescription'] = $descriptionValue;
                }
            }
        }
        if ($date = $reader->firstValue($resourceSet, 'resourceDateTaken/displayDate')) {
            $result['dateTaken'] = $date;
        }
        foreach ($reader->all($resourceSet, 'resourcePerspective') as $perspective) {
            if ($term = $this->getLanguageSpecificValueByPath($perspective, 'term', $language)) {
                $result['perspectives'][] = $term;
            }
        }
        return $result;
    }

    /**
     * Function to return model as associative array
     * - format Model format as key
     *   - type Model type preview_3d or provided_3d as key
     *          url to model as value.
     *
     * @param string  $url          Model url
     * @param string  $format       Model format
     * @param string  $type         Model type
     * @param array[] $measurements Measurements nodes
     *
     * @return array
     */
    protected function getModel(
        string $url,
        string $format,
        string $type,
        array $measurements
    ): array {
        $type = $this->modelTypes[$type];
        $format = strtolower($format);
        if ('preview' !== $type || !in_array($format, $this->displayableModelFormats)) {
            return [];
        }
        $model = [
            'url' => $url,
            'format' => $format,
            'type' => $type,
        ];
        if ($measurements) {
            $model['data'] = $this->formatResourceMeasurements($measurements);
        }
        return $model;
    }

    /**
     * Function to return image in associative array
     * - displayImage
     *  - small                 Image size with url as value
     *  - medium                Image size with url as value
     *  - large                 Image size with url as value
     * - highResolution
     *  - size                  Image size master or original
     *      - format            Image format as key
     *          - data          Contains data like measurements
     *          - resourceID    ID to which resource belongs to
     *          - url           Url of the image.
     *
     * @param string  $url          Url of the resourceset
     * @param string  $type         Type of the image
     * @param string  $language     Language to get information
     * @param string  $id           ID of the resourceset
     * @param string  $format       Format of the image
     * @param array[] $measurements Measurements nodes
     *
     * @return array
     */
    protected function getImage(
        string $url,
        string $type,
        string $language,
        string $id = '',
        string $format = '',
        array $measurements = []
    ): array {
        // Check if the image is really an image
        // Original images can be any type and are not displayed
        if ('image_original' !== $type && $this->isUndisplayableFormat($format)) {
            return [];
        }

        $size = $this->imageTypes[$type];
        $displayImage = [];
        if ($size !== 'original') {
            $displayImage[$size] = $url;
        }
        $highResolution = [];
        if (in_array($size, ['master', 'original'])) {
            $currentHiRes = [
                'data' => $this->formatResourceMeasurements($measurements),
                'url' => $url,
                'format' => $format ?: 'jpg',
            ];
            if ($id) {
                $currentHiRes['resourceID'] = $id;
            }
            $highResolution[$size][] = $currentHiRes;
        }

        return compact('displayImage', 'highResolution');
    }

    /**
     * Function to return an audio in associative array
     * - desc   Default is null
     * - url    Url to audio file
     * - codec  Codec type of the audio
     * - type   Type what type is the audio file
     * - embed  Type of embed is audio.
     *
     * @param string  $url          Url of the audio
     * @param string  $format       Format of the audio
     * @param string  $description  Description of the audio
     * @param array[] $measurements Measurements nodes
     *
     * @return array
     */
    protected function getAudio(
        string $url,
        string $format,
        string $description,
        array $measurements
    ): array {
        if ($this->supportedAudioFormats[$format] ?? false) {
            if ($this->maxAmountOfURLs()) {
                $this->urlsCount++;
                return [];
            }
            $audio = [
                'desc' => $description ?: null,
                'url' => $url,
                'codec' => $format,
                'type' => 'audio',
                'embed' => 'audio',
            ];
            if ($measurements) {
                $audio['data'] = $this->formatResourceMeasurements($measurements);
            }
            return $audio;
        }
        return [];
    }

    /**
     * Function to return a video in associative array
     * - desc           Default is null
     * - url            Video url
     * - embed          Video embed is video
     * - videosources
     *  - src           Different sources for the video
     *  - type          Codec type
     * - downloadable   True if video is allowed to be downloaded.
     *
     * @param string  $url          Url of the video
     * @param string  $format       Format of the video
     * @param string  $description  Description of the video
     * @param array   $rights       Array of video rights
     * @param array[] $measurements Measurements nodes
     *
     * @return array
     */
    protected function getVideo(
        string $url,
        string $format,
        string $description,
        array $rights,
        array $measurements,
    ): array {
        $mediaType = $this->supportedVideoFormats[$format] ?? false;
        if ($this->maxAmountOfURLs()) {
            $this->urlsCount++;
            return [];
        }
        $video = match ($mediaType) {
            'text/html' => [
                'desc' => $description ?: null,
                'url' => $url,
                'embed' => 'iframe',
                'format' => $format,
            ],
            false => [],
            default => [
                'desc' => $description ?: null,
                'url' => $url,
                'embed' => 'video',
                'format' => $format,
                'videoSources' => [
                    'src' => $url,
                    'type' => $mediaType,
                ],
                'downloadable' => $this->allowRecordImageDownload(compact('rights')),
            ],
        };
        if ($video && $measurements) {
            $video['data'] = $this->formatResourceMeasurements($measurements);
        }
        return $video;
    }

    /**
     * Function to return document in associative array.
     *
     * @param string $url         Url of the document
     * @param string $format      Format of the document
     * @param string $description Description of the document
     * @param array  $rights      Array of document rights
     * @param bool   $linkType    Type of document link, default is 'proxy-link'.
     * @param string $type        Type of resource.
     *
     * @return array
     */
    protected function getDocument(
        string $url,
        string $format,
        string $description,
        array $rights,
        string $linkType = 'proxy-link',
        string $type = '',
    ): array {
        if ($this->maxAmountOfURLs()) {
            $this->urlsCount++;
            return [];
        }
        $format = strtolower($format);
        // Do not display text/html mediatype
        if ('text/html' === $format) {
            $format = '';
        }
        $label = $type === 'provided_3D' ? '3D' : '';
        return [
            'description' => $description ?: false,
            'url' => $url,
            'format' => $format,
            'rights' => $rights,
            'linkType' => $linkType,
            'label' => $label,
        ];
    }

    /**
     * Get rights from the given resourceSet.
     *
     * @param array  $resourceSet Given resourceSet from lido
     * @param string $language    Language to look for
     * @param bool   $useDefault  Use default rights as fallback, default true
     *
     * @return array
     */
    protected function getResourceRights(
        array $resourceSet,
        string $language,
        bool $useDefault = true
    ): array {
        $rights = [];
        $reader = $this->getXmlReader();
        foreach ($reader->all($resourceSet, 'rightsResource') as $rightsResource) {
            foreach ($reader->all($rightsResource, 'rightsType/conceptID') as $conceptID) {
                if ('' !== ($conceptValue = $reader->value($conceptID) ?? '')) {
                    $rights['copyright'] = $this->getMappedRights($conceptValue);
                    $link = $this->getRightsLink($rights['copyright'], $language);
                    if ($link) {
                        $rights['link'] = $link;
                    }
                }
            }

            foreach ($reader->all($rightsResource, 'rightsHolder') as $rightsHolder) {
                if (!($name = $reader->firstValue($rightsHolder, 'legalBodyName/appellationValue'))) {
                    continue;
                }
                $link = $reader->firstValue($rightsHolder, 'legalBodyWeblink');
                $rightsHolder = compact('name', 'link');
                $rights['rightsHolders'][] = $rightsHolder;
            }

            if ($creditLine = $this->getLanguageSpecificValueByPath($rightsResource, 'creditLine', $language)) {
                $rights['creditLine'] = $creditLine;
            }
        }

        if ($term = $this->getLanguageSpecificValueByPath($resourceSet, 'rightsResource/rightsType/term', $language)) {
            if (($rights['copyright'] ?? null) !== $term) {
                $rights['description'][] = $term;
            }
        }

        if ($rights) {
            return $rights;
        }
        return $useDefault ? ($this->getImageRights($language, true) ?: []) : [];
    }

    /**
     * Return model settings from config.
     *
     * @return array settings
     */
    public function getModelSettings(): array
    {
        $settings = [];
        if ($iniData = $this->recordConfig->Models ?? []) {
            $settings = [
                'debug' => boolval($iniData->debug ?? 0),
                'previewImages' => $this->allowModelPreviewImages(),
            ];
        }

        return $settings;
    }

    /**
     * Return all labels for the record.
     *
     * @return array
     */
    public function getLabels(): array
    {
        if ($this->has3DResources()) {
            return array_merge($this->labels, [['label' => '3D', 'class' => 'resource-type']]);
        }
        return $this->labels;
    }

    /**
     * If record has 3D resources.
     *
     * @return bool
     */
    public function has3DResources(): bool
    {
        if ($this->getModels()) {
            return true;
        }
        return in_array('3D', array_column($this->getDocuments(), 'label'));
    }

    /**
     * Can model preview images be shown.
     *
     * @return bool
     */
    public function allowModelPreviewImages(): bool
    {
        $datasource = $this->getDataSource();
        return !empty($this->mainConfig->Models->previewImages[$datasource]);
    }

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->fields[$this->getPrioritizedTitleField()] ?? '';
    }

    /**
     * Get an array of alternative titles for the record.
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        return $this->compareWithTitle($this->getAllTitles());
    }

    /**
     * Get an array of related publications for the record.
     *
     * @return array
     */
    public function getRelatedPublications()
    {
        $reader = $this->getXmlReader();
        $results = [];
        foreach (
            $reader->all(path: 'lido/descriptiveMetadata/objectRelationWrap/relatedWorksWrap/relatedWorkSet') as $node
        ) {
            if ($title = $searchTitle = ($reader->firstValue($node, 'relatedWork/displayObject') ?? '')) {
                $term = $reader->firstValue($node, 'relatedWorkRelType/term') ?? '';
                $termLC = $this->toLower($term);
                if (!in_array($termLC, $this->relatedPulicationRelationTypes)) {
                    continue;
                }
                $label = $reader->attr($reader->first($node, 'relatedWork/displayObject'), 'label') ?? '';
                $term = !in_array($termLC, ['julkaisu', 'is reproduced in']) ? $term : '';
                // Check if title can be used as search link.
                // Discard titles that are extremely long as they usually contain excessive information
                // or contain semicolons which are commonly used to combine multiple titles in one field.
                if (
                    in_array($this->toLower($label), $this->relatedPulicationTitlesExcludedFromSearch)
                    || strlen($searchTitle) > 400
                    || str_contains($searchTitle, ';')
                ) {
                    $searchTitle = '';
                }
                // Remove page numbers like "s. 36-38, s. 196" from the end of the search title
                if (preg_match('{^(.*?),[s\.,\-–\s\d]+$}', $searchTitle, $matches)) {
                    $searchTitle = $matches[1];
                }
                // Limit search title to 30 words for better result
                if (preg_match('{((.+?\s+){29}\S*).*}', $searchTitle, $matches)) {
                    $searchTitle = $matches[1];
                }
                $isbn = '';
                foreach ($reader->allValues($node, 'relatedWork/object/objectID') as $identifier) {
                    $trimmed = trim(preg_replace('/\s+/', ' ', $identifier));
                    if (preg_match('{^(URN:ISBN:)(.*)}', $trimmed, $matches)) {
                        $isbn = trim($matches[2]);
                        continue;
                    }
                }
                $results[] = [
                    'title' => $title,
                    'searchTitle' => $searchTitle,
                    'label' => $label ?: $term,
                    'url' => '',
                    'isbn' => $isbn,
                ];
            }
        }
        return $results;
    }

    /**
     * Get online URLs.
     *
     * @param bool  $raw          Whether to return raw data
     * @param array $excludeTypes If set, will remove types of urls from result
     *
     * @return array
     */
    public function getOnlineURLs($raw = false, $excludeTypes = []): array
    {
        return $this->getDocuments();
    }

    /**
     * Get an array of classifications for the record.
     *
     * @return array
     */
    public function getOtherClassifications()
    {
        $reader = $this->getXmlReader();
        $results = $langNodes = [];
        $path = 'lido/descriptiveMetadata/objectClassificationWrap/classificationWrap/classification';
        foreach ($reader->all(path: $path) as $node) {
            if (!in_array($reader->attr($node, 'type'), $this->excludedClassifications)) {
                $langNodes = [
                    ...$langNodes,
                    ...$reader->all($node, 'term'),
                ];
            }
        }
        $langNodes = $this->getAllLanguageSpecificNodes($langNodes, $this->getLocale(), true);
        foreach ($langNodes as $langNode) {
            if ($term = $reader->value($langNode)) {
                $label = $reader->attr($langNode, 'label');
                $results[] = $label ? compact('term', 'label') : $term;
            }
        }
        return $results;
    }

    /**
     * Return attributes of an element as an associative array.
     * - id            Id attribute
     * - source        Source attribute.
     *
     * @param array $conceptID The element to get attributes from
     *
     * @return array
     */
    protected function getSubjectConceptIdAttributes(array $conceptID): array
    {
        $reader = $this->getXmlReader();
        $results = [];
        if ($id = $reader->value($conceptID)) {
            $type = $this->toLower($reader->attr($conceptID, 'type') ?? '');
            if (in_array($type, $this->subjectConceptIDTypes)) {
                $results['id'] = $id;
                $results['source'] = $reader->attr($conceptID, 'source') ?? '';
            }
        }
        return $results;
    }

    /**
     * Return attributes of any conceptID nodes of a node as an associative array.
     * - id            Id attribute
     * - source        Source attribute.
     *
     * @param array $parentNode The node that contains conceptID nodes
     *
     * @return array
     */
    protected function getFirstConceptIdAttributes(array $parentNode): array
    {
        $reader = $this->getXmlReader();
        foreach ($reader->all($parentNode, 'conceptID') as $conceptID) {
            if ($values = $this->getSubjectConceptIdAttributes($conceptID)) {
                return $values;
            }
        }
        return [];
    }

    /**
     * Get the collections of the current record.
     *
     * @return array
     */
    public function getCollections()
    {
        $reader = $this->getXmlReader();
        $results = [];
        $allowedTypes = ['Kokoelma', 'kuuluu kokoelmaan', 'kokoelma', 'Alakokoelma',
            'Erityiskokoelma'];
        foreach (
            $reader->all(path: 'lido/descriptiveMetadata/objectRelationWrap/relatedWorksWrap/relatedWorkSet') as $set
        ) {
            $term = $reader->firstValue($set, 'relatedWorkRelType/term');
            if (in_array($term, $allowedTypes)) {
                foreach ($reader->all($set, 'relatedWork/displayObject') as $object) {
                    if ('' !== $reader->value($object)) {
                        $results[] = $object;
                    }
                }
            }
        }
        return $this->getAllLanguageSpecificValues($results, $this->getLocale());
    }

    /**
     * Get archive type.
     *
     * @return string
     */
    public function getArchiveType(): string
    {
        return 'collection';
    }

    /**
     * Get an array of events for the record.
     *
     * @return array
     */
    public function getEvents()
    {
        $events = [];
        $language = $this->getLocale();
        $reader = $this->getXmlReader();
        foreach ($reader->all(path: 'lido/descriptiveMetadata/eventWrap/eventSet/event') as $node) {
            $name = $reader->firstValue($node, 'eventName/appellationValue') ?? '';
            $type = $this->toLower($reader->firstValue($node, 'eventType/term') ?? '');
            $date = $this->getLanguageSpecificValueByPath($node, 'eventDate/displayDate', $language);
            if (!$date && $dateNode = $reader->first($node, 'eventDate/date')) {
                $startDate = $reader->firstValue($dateNode, 'earliestDate') ?? '';
                $endDate = $reader->firstValue($dateNode, 'latestDate') ?? '';
                if (strlen($startDate) == 4 && strlen($endDate) == 4) {
                    $date = "$startDate-$endDate";
                } else {
                    $startDateType = 'Y-m-d';
                    $endDateType = 'Y-m-d';
                    if (strlen($startDate) == 7) {
                        $startDateType = 'Y-m';
                    }
                    if (strlen($endDate) == 7) {
                        $endDateType = 'Y-m';
                    }

                    $date = $this->dateConverter && $startDate
                        ? $this->dateConverter->convertToDisplayDate(
                            $startDateType,
                            $startDate
                        )
                        : $startDate;

                    if ($endDate && $startDate != $endDate) {
                        $date .= ' - ' . ($this->dateConverter
                            ? $this->dateConverter->convertToDisplayDate(
                                $endDateType,
                                $endDate
                            )
                            : $endDate);
                    }
                }
            }
            if ($type == 'valmistus') {
                $confParam = 'lido_augment_display_date_with_period';
                if ($this->getDataSourceConfigurationValue($confParam)) {
                    if ($period = $reader->firstValue($node, 'periodName/term')) {
                        $date = $date ? $period . ', ' . $date : $period;
                    }
                }
            }
            $methods = [];
            $methodsExtended = [];
            $langMethodsExtended = [];
            foreach ($reader->all($node, 'eventMethod') as $eventMethod) {
                $id = $source = '';
                if ($values = $this->getFirstConceptIdAttributes($eventMethod)) {
                    $id = $values['id'];
                    $source = $values['source'];
                }
                foreach ($reader->all($eventMethod, 'term') as $term) {
                    $termStr = $reader->value($term);
                    if ($termStr === '') {
                        continue;
                    }
                    $methods[] = $termStr;
                    $methodsExtended[] = [
                        'data' => $termStr,
                        'id' => $id,
                        'source' => $source,
                    ];
                    $lang = $reader->attr($term, 'lang');
                    if ($lang === $language) {
                        $langMethodsExtended[] = [
                            'data' => $termStr,
                            'id' => $id,
                            'source' => $source,
                        ];
                    }
                }
            }
            $materials = [];
            $materialsExtended = [];
            $langMaterialsExtended = [];
            foreach ($reader->all($node, 'eventMaterialsTech') as $eventMaterialsTech) {
                $display
                    = $this->getLanguageSpecificValueByPath($eventMaterialsTech, 'displayMaterialsTech', $language);
                if ($display) {
                    $materials[] = $display;
                    $langMaterialsExtended[] = [
                        'data' => $display,
                        'id' => '',
                        'source' => '',
                    ];
                    continue;
                }
                foreach ($reader->all($eventMaterialsTech, 'materialsTech') as $materialsTech) {
                    foreach ($reader->all($materialsTech, 'termMaterialsTech') as $termMaterialsTech) {
                        $id = $source = '';
                        if ($values = $this->getFirstConceptIdAttributes($termMaterialsTech)) {
                            $id = $values['id'];
                            $source = $values['source'];
                        }
                        foreach ($reader->all($termMaterialsTech, 'term') as $term) {
                            $termStr = $reader->value($term);
                            if ($termStr === '') {
                                continue;
                            }
                            $label = null;
                            $lang = $reader->attr($term, 'lang');
                            // Musketti
                            $label = $reader->attr($term, 'label');
                            if (!$label) {
                                // Siiri
                                $label = $reader->attr($reader->first($materialsTech, 'extentMaterialsTech'), 'label');
                            }
                            if ($label) {
                                $termStr = "$termStr ($label)";
                            }
                            $materialsExtended[] = [
                                'data' => $termStr,
                                'id' => $id,
                                'source' => $source,
                            ];
                            if ($lang === $language) {
                                $langMaterialsExtended[] = [
                                    'data' => $termStr,
                                    'id' => $id,
                                    'source' => $source,
                                ];
                            }
                            $materials[] = $termStr;
                        }
                    }
                }
            }

            $places = [];
            foreach ($reader->all($node, 'eventPlace') as $eventPlace) {
                $displayPlace = trim($reader->firstValue($eventPlace, 'displayPlace') ?? '', ", \n\r\t\v\0");
                $placeId = $reader->first($eventPlace, 'place/placeID');
                if (!$displayPlace) {
                    // Gather display name from placeNameSet:
                    $partOfPlaceName = [];
                    foreach ($reader->all($eventPlace, 'place/namePlaceSet') as $nameSet) {
                        $value = $reader->firstValue($nameSet, 'appellationValue') ?? '';
                        if ('' !== $value) {
                            $partOfPlaceName[] = $value;
                        }
                    }
                    foreach ($reader->all($eventPlace, 'place/partOfPlace') as $part) {
                        while ($part) {
                            $appellationValue = $reader->firstValue($part, 'namePlaceSet/appellationValue') ?? '';
                            if ('' !== $appellationValue) {
                                $partOfPlaceName[] = $appellationValue;
                            }
                            $part = $reader->first($part, 'partOfPlace');
                        }
                    }
                    if (!$partOfPlaceName) {
                        continue;
                    }
                    $displayPlace = implode(', ', $partOfPlaceName);
                }
                if ($placeId) {
                    $placeData = [
                        'placeName' => $displayPlace,
                    ];
                    $idTypeFirst = $this->getPlaceIDType($placeId);
                    $prependType = $idTypeFirst !== ''
                        && !in_array(strtolower($idTypeFirst), $this->uniquePlaceIDTypes);
                    $placeData['type'] = $idTypeFirst;
                    $placeIdStr = $reader->value($placeId);
                    $placeData['id'] = $prependType ? "($idTypeFirst)$placeIdStr" : $placeIdStr;
                    foreach ($reader->all($eventPlace, 'place/placeID') as $item) {
                        $details = [];
                        $id = $reader->value($item);
                        $idType = $this->getPlaceIDType($item);
                        $prependType = $idType !== '' && !in_array(strtolower($idType), $this->uniquePlaceIDTypes);
                        $placeData['ids'][] = $prependType ? "($idType)$id" : $id;
                        $typeDesc = $idType ? 'place_id_type_' . $idType : '';
                        $details[] = $typeDesc;
                        if ($typeDesc) {
                            $placeData['details'] = $details;
                        }
                    }
                    $places[] = $placeData;
                } else {
                    $places[] = $displayPlace;
                }
            }

            $actors = [];
            foreach ($reader->all($node, 'eventActor') as $actor) {
                $appellationValue
                    = $reader->firstValue($actor, 'actorInRole/actor/nameActorSet/appellationValue') ?? '';
                if ('' !== $appellationValue) {
                    $role = '';
                    $langRoles = $reader->all($actor, 'actorInRole/roleActor/term');
                    if ($langRoles) {
                        $roles = $this->getAllLanguageSpecificValues($langRoles, $language);
                        $role = implode(', ', $roles);
                    }
                    $earliestDate = $reader->firstValue($actor, 'actorInRole/actor/vitalDatesActor/earliestDate')
                        ?? '';
                    $latestDate = $reader->firstValue($actor, 'actorInRole/actor/vitalDatesActor/latestDate')
                        ?? '';
                    $id = $this->getPrimaryActorId($actor);
                    $actors[] = [
                        'name' => $appellationValue,
                        'role' => $role,
                        'birth' => $earliestDate,
                        'death' => $latestDate,
                        'id' => $id,
                    ];
                }
            }
            $culture = $reader->firstValue($node, 'culture/term') ?? '';
            $descriptions = [];
            $desc = $this->getLanguageSpecificValueByPath($node, 'eventDescriptionSet/descriptiveNoteValue', $language);
            if ($desc) {
                $descriptions[] = $desc;
            }
            $event = [
                'type' => $type,
                'name' => $name,
                'date' => $date,
                'methods' => $methods,
                'methodsExtended' => $langMethodsExtended ?: $methodsExtended,
                'materials' => $materials,
                'materialsExtended' => $langMaterialsExtended ?: $materialsExtended,
                'places' => $places,
                'actors' => $actors,
                'culture' => $culture,
                'descriptions' => $descriptions,
                // For backward compatibility
                'description' => $descriptions[0] ?? '',
            ];
            // Only add the event if it has content
            foreach ($event as $key => $field) {
                if ('type' !== $key && !empty($field)) {
                    $events[$type][] = $event;
                    break;
                }
            }
        }
        return $events;
    }

    /**
     * Get identifier.
     *
     * @return array
     */
    public function getIdentifier()
    {
        foreach ($this->getIdentifiersByType(true) as $identifier) {
            // Take only the first identifier here
            // since others are included in getLocalIdentifiers
            return [$identifier];
        }
        return [];
    }

    /**
     * Return image rights.
     *
     * @param string $language       Language
     * @param bool   $skipImageCheck Whether to check that images exist
     *
     * @return mixed array with keys:
     *   'copyright'  Copyright (e.g. 'CC BY 4.0') (optional)
     *   'description Human readable description (array)
     *   'link'       Link to copyright info
     *   or false if the record contains no images
     */
    public function getImageRights($language, $skipImageCheck = false)
    {
        if (!$skipImageCheck && !$this->getAllImages()) {
            return false;
        }

        $rights = $this->getAccessRestrictionsType($language) ?: [];
        $desc = $this->getAccessRestrictions($language);
        if ($desc && count($desc)) {
            $description = [];
            foreach ($desc as $p) {
                $description[] = (string)$p;
            }
            $rights['description'] = $description;
        }

        return !empty($rights['copyright']) || !empty($rights['description'])
            ? $rights : false
        ;
    }

    /**
     * Get an array of inscriptions for the record.
     *
     * @return array
     */
    public function getInscriptions()
    {
        $reader = $this->getXmlReader();
        $results = [];
        $path = 'lido/descriptiveMetadata/objectIdentificationWrap/inscriptionsWrap/inscriptions';
        foreach ($reader->all(path: $path) as $inscriptions) {
            $group = [];
            foreach ($reader->all($inscriptions, 'inscriptionDescription') as $node) {
                if (!($descriptiveNoteValue = $reader->first($node, 'descriptiveNoteValue'))) {
                    continue;
                }
                if ('' === ($content = $reader->value($descriptiveNoteValue) ?? '')) {
                    continue;
                }
                $type = $this->toLower($reader->attr($node, 'type') ?? '');
                $type = $this->inscriptionTypeMappings[$type] ?? $type;
                $label = $reader->attr($descriptiveNoteValue, 'label') ?? '';
                $group[] = compact('type', 'label', 'content');
            }
            if ($group) {
                $results[] = $group;
            }
        }
        return $results;
    }

    /**
     * Get an array of local identifiers for the record.
     *
     * @return array
     */
    public function getLocalIdentifiers()
    {
        $results = [];
        $identifiers = $this->getIdentifiersByType(true, [], ['isbn', 'issn']);
        $primaryID = $this->getIdentifier();
        foreach ($identifiers as $identifier) {
            if (!in_array($identifier, $primaryID)) {
                $results[] = $identifier;
            }
        }
        return $results;
    }

    /**
     * Get an array of ISBNs for the record.
     *
     * @return array
     */
    public function getISBNs()
    {
        return $this->getIdentifiersByType(false, ['isbn']);
    }

    /**
     * Get an array of ISBNs for the record.
     *
     * @return array
     */
    public function getISSNs()
    {
        return $this->getIdentifiersByType(false, ['issn']);
    }

    /**
     * Get the main format.
     *
     * @return array
     */
    public function getMainFormat()
    {
        if (!isset($this->fields['format'])) {
            return 'Other';
        }
        $formats = $this->fields['format'];
        $format = reset($formats);
        $format = preg_replace('/^\d+\/([^\/]+)\/.*/', '\1', $format);
        return $format;
    }

    /**
     * Get measurements.
     *
     * @return array
     */
    public function getMeasurements(): array
    {
        return $this->getMeasurementsByType();
    }

    /**
     * Get extent.
     *
     * @return array
     */
    public function getPhysicalDescriptions(): array
    {
        return $this->getMeasurementsByType(['extent']);
    }

    /**
     * Get measurements by type.
     *
     * @param array $include Measurement types to include, otherwise all but
     * excluded types
     *
     * @return array
     */
    public function getMeasurementsByType(array $include = []): array
    {
        $results = [];
        $exclude = $include ? [] : $this->excludedMeasurements;
        $language = $this->getLocale();
        $reader = $this->getXmlReader();
        $objectMeasurementsSets = $reader->all(
            path: 'lido/descriptiveMetadata/objectIdentificationWrap/objectMeasurementsWrap/objectMeasurementsSet'
        );
        foreach ($objectMeasurementsSets as $set) {
            // Get set extents to be displayed
            $extentNodes = $reader->all($set, 'objectMeasurements/extentMeasurements');
            $extentMeasurements = $this->getAllLanguageSpecificValues($extentNodes, $language, true);
            $displayExtents = implode(', ', $extentMeasurements);
            // Use display element with allowed type
            $displayNode = $this->getLanguageSpecificNode($reader->all($set, 'displayObjectMeasurements'), $language);
            if ($displayNode && ($displayMeasurements = $reader->value($displayNode))) {
                $label = $reader->attr($displayNode, 'label') ?? '';
                if (($include && !in_array($label, $include)) || ($exclude && in_array($label, $exclude))) {
                    continue;
                }
                $results[] = $displayExtents ? "$displayMeasurements ($displayExtents)" : $displayMeasurements;
                continue;
            }
            // Use measurementsSet only if no display elements exist
            foreach ($reader->all($set, 'objectMeasurements/measurementsSet') as $measurements) {
                $type = $reader->firstValue($measurements, 'measurementType/term') ?? '';
                if (($include && !in_array($type, $include)) || ($exclude && in_array($type, $exclude))) {
                    continue;
                }
                $parts = [];
                if ($type = $this->getLanguageSpecificValueByPath($measurements, 'measurementType', $language)) {
                    $parts[] = $type;
                }
                if ($val = $this->getLanguageSpecificValueByPath($measurements, 'measurementValue', $language)) {
                    $parts[] = $val;
                }
                if ($unit = $this->getLanguageSpecificValueByPath($measurements, 'measurementUnit', $language)) {
                    $parts[] = $unit;
                }
                if ($combined = implode(' ', $parts)) {
                    $results[] = $displayExtents ? "$combined ($displayExtents)" : $combined;
                }
            }
        }
        return $results;
    }

    /**
     * Get all authors apart from presenters.
     *
     * @return array
     */
    public function getNonPresenterAuthors()
    {
        $reader = $this->getXmlReader();
        $authors = [];
        $index = 0;
        $language = $this->getLocale();
        foreach ($reader->all(path: 'lido/descriptiveMetadata/eventWrap/eventSet/event') as $event) {
            $eventType = $this->toLower($reader->firstValue($event, 'eventType/term') ?? '');
            $priority = $this->authorEvents[$eventType] ?? null;
            if (null === $priority) {
                continue;
            }
            foreach ($reader->all($event, 'eventActor') as $actor) {
                $name = $reader->firstValue($actor, 'actorInRole/actor/nameActorSet/appellationValue') ?? '';
                if ($name) {
                    $role = '';
                    if ($langRoles = $reader->all($actor, 'actorInRole/roleActor/term')) {
                        $roles = $this->getAllLanguageSpecificValues($langRoles, $language);
                        $role = implode(', ', $roles);
                    }
                    $id = $this->getPrimaryActorId($actor);
                    $key = $priority * 1000 + $index++;
                    $authors[$key] = compact(
                        'name',
                        'role',
                        'id'
                    );
                }
            }
        }
        ksort($authors, SORT_NUMERIC);
        return array_values($authors);
    }

    /**
     * Get hierarchy parent archives.
     *
     * @return array
     */
    public function getParentArchives()
    {
        return $this->getParentLinksByType('archive');
    }

    /**
     * Get hierarchy parent collections.
     *
     * @return array
     */
    public function getParentCollections()
    {
        return $this->getParentLinksByType('collection');
    }

    /**
     * Get hierarchy parent subcollections.
     *
     * @return array
     */
    public function getParentSubcollections()
    {
        return $this->getParentLinksByType('subcollection');
    }

    /**
     * Get hierarchy parent series.
     *
     * @return array
     */
    public function getParentSeries()
    {
        return $this->getParentLinksByType('series');
    }

    /**
     * Get hierarchy parent purchase batches.
     *
     * @return array
     *
     * @deprecated No longer used
     */
    public function getParentPurchaseBatches()
    {
        return [];
    }

    /**
     * Get hierarchy parent unclassified entities.
     *
     * Returns entities not belonging to any of the separately handled classes.
     *
     * @return array
     */
    public function getParentUnclassifiedEntities()
    {
        return $this->getParentLinksByType('');
    }

    /**
     * Get hierarchy parent works.
     *
     * @return array
     */
    public function getParentWorks()
    {
        return $this->getParentLinksByType('work');
    }

    /**
     * Get an array of dates for results list display.
     *
     * @return ?array Array of one or two dates or null if not available.
     * If date range is still continuing end year will be an empty string.
     */
    public function getResultDateRange()
    {
        return $this->getDateRange('creation');
    }

    /**
     * Get subject actors.
     *
     * @param bool $extended Whether to return a keyed array with the following keys:
     * - name: name of the actor
     * - id: primary authority id (if defined)
     *
     * @return array
     */
    public function getSubjectActors(bool $extended = false): array
    {
        $reader = $this->getXmlReader();
        $results = [];
        $path = 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/subjectSet/subject/subjectActor';
        foreach ($reader->all(path: $path) as $subjectActor) {
            if ($name = $reader->firstValue($subjectActor, 'actor/nameActorSet/appellationValue')) {
                $results[] = $extended ? [
                    'name' => $name,
                    'id' => $this->getPrimaryActorId($subjectActor),
                ] : $name;
            }
        }
        return $results;
    }

    /**
     * Get extended subject actors.
     *
     * @return array
     */
    public function getSubjectActorsExtended(): array
    {
        return $this->getSubjectActors(true);
    }

    /**
     * Get subject dates.
     *
     * @return array
     */
    public function getSubjectDates()
    {
        $reader = $this->getXmlReader();
        $results = [];
        $language = $this->getLocale();
        $path = 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/subjectSet/subject';
        foreach ($reader->all(path: $path) as $node) {
            if ($term = $this->getLanguageSpecificValueByPath($node, 'subjectDate/displayDate', $language)) {
                $results[] = $term;
            }
        }
        return $results;
    }

    /**
     * Get subject details.
     *
     * @return array
     */
    public function getSubjectDetails()
    {
        $reader = $this->getXmlReader();
        $results = [];
        $path = 'lido/descriptiveMetadata/objectIdentificationWrap/titleWrap/titleSet/appellationValue';
        foreach ($reader->all(path: $path) as $node) {
            if ($reader->attr($node, 'label') === 'aiheen tarkenne') {
                $results[] = $reader->value($node);
            }
        }
        return $results;
    }

    /**
     * Return all subject headings.
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading
     * - type: heading type
     * - source: source vocabulary
     * - id: first authority id (if defined)
     * - ids: multiple authority ids (if defined)
     * - authType: authority type (if id is defined)
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false)
    {
        $headings = $this->getAllSubjectHeadingsForDisplay($extended);
        foreach ($this->getSubjectActors($extended) as $actor) {
            $headings[] = $extended ? [
                'heading' => [$actor['name']],
                'type' => 'topic',
                'id' => $actor['id'],
            ] : [$actor];
        }
        if ($places = $this->getSubjectPlaces($extended, false)) {
            $headings = [...$headings, ...$places];
        }
        // Ensure that all the values are an array
        if (!$extended) {
            foreach ($headings as &$value) {
                if (!is_array($value)) {
                    $value = [$value];
                }
            }
            unset($value);
        }
        return $headings;
    }

    /**
     * Get all subject headings associated with this record apart from geographic
     * places and subject actors. Each heading is returned as an array of chunks, increasing from least
     * specific to most specific.
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading chunks
     * - type: heading type
     * - source: source vocabulary
     * - id: first authority id (if defined)
     * - ids: multiple authority ids (if defined)
     * - authType: authority type (if id is defined)
     *
     * @return array
     */
    public function getAllSubjectHeadingsForDisplay(bool $extended = false): array
    {
        $headings = [];
        $headings = $this->getTopics();
        $language = $this->getLocale();

        $headings = array_merge(
            $headings,
            array_map(
                function ($term) {
                    return ['data' => $term];
                },
                $this->fields['genre'] ?? []
            )
        );
        // Include all display dates from events except creation date
        $reader = $this->getXmlReader();
        foreach ($reader->all(path: 'lido/descriptiveMetadata/eventWrap/eventSet/event') as $event) {
            $type = $this->toLower($reader->firstValue($event, 'eventType/term'));
            if (!in_array($type, $this->nonPlaceEvents)) {
                if ($date = $this->getLanguageSpecificValueByPath($event, 'eventDate/displayDate', $language)) {
                    $headings[] = ['data' => $date];
                }
            }
        }

        // The default index schema doesn't currently store subject headings in a
        // broken-down format, so we'll just send each value as a single chunk.
        // Other record drivers (i.e. SolrMarc) can offer this data in a more
        // granular format.
        $callback = function ($i) use ($extended) {
            if ($extended) {
                $data = [
                    'heading' => [$i['data']],
                    'type' => 'topic',
                    'source' => $i['source'] ?? '',
                ];
                if ($id = $i['id'] ?? '') {
                    $data['id'] = $id;
                    // Categorize non-URI ID's as Unknown Names, since the
                    // actual authority format can not be determined from metadata.
                    $data['authType'] = preg_match('/^https?:/', $id)
                        ? null : 'Unknown Name';
                }
            } else {
                return [$i['data']];
            }
            return $data;
        };
        return array_map($callback, $headings);
    }

    /**
     * Get extended subject information.
     *
     * @return array
     */
    public function getAllSubjectHeadingsForDisplayExtended(): array
    {
        return $this->getAllSubjectHeadingsForDisplay(true);
    }

    /**
     * Get topics.
     *
     * @return array
     */
    public function getTopics(): array
    {
        $reader = $this->getXmlReader();
        $topics = [];
        $langTopics = [];
        $language = $this->getLocale();
        foreach (
            $reader->all(path: 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/subjectSet/subject') as $subject
        ) {
            $type = $reader->attr($subject, 'type');
            if (in_array($this->toLower($type), $this->excludedSubjectTypes)) {
                continue;
            }
            foreach ($reader->all($subject, 'subjectConcept') as $concept) {
                $id = $source = '';
                if ($values = $this->getFirstConceptIdAttributes($concept)) {
                    $id = $values['id'];
                    $source = $values['source'];
                }
                foreach ($reader->all($concept, 'term') as $term) {
                    if ('' === ($str = $reader->value($term) ?? '')) {
                        continue;
                    }
                    $langAttr = $reader->attr($term, 'lang');
                    if ($id !== '') {
                        $topics[] = [
                            'data' => $str,
                            'id' => $id,
                            'source' => $source,
                        ];
                        if ($langAttr === $language) {
                            $langTopics[] = [
                                'data' => $str,
                                'id' => $id,
                                'source' => $source,
                            ];
                        }
                    } else {
                        foreach (explode(',', $reader->value($term) ?? '') as $explodedTerm) {
                            if ('' !== ($str = trim($explodedTerm))) {
                                $topics[] = ['data' => $str];
                                if ($langAttr === $language) {
                                    $langTopics[] = ['data' => $str];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $langTopics ? $langTopics : $topics;
    }

    /**
     * Get subject places.
     *
     * @param bool $extended    Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading chunks
     * - type: heading type
     * - detail: addition details
     * - source: source vocabulary
     * - id: authority id (if defined)
     * - ids: multiple authority ids (if defined)
     * - authType: authority type (if id is defined)
     * @param bool $prependType Should type be prepended in front of an id. Default is true.
     *
     * @return array
     */
    public function getSubjectPlaces(bool $extended = false, bool $prependType = true)
    {
        $reader = $this->getXmlReader();
        $results = [];
        $path = 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/subjectSet/subject/subjectPlace';
        foreach ($reader->all(path: $path) as $subjectPlace) {
            $displayPlace = trim($reader->firstValue($subjectPlace, 'displayPlace') ?? '', ", \n\r\t\v\0");
            if ('' === $displayPlace) {
                $placeNames = $reader->allValues($subjectPlace, 'place/namePlaceSet/appellationValue');
                foreach ($reader->all($subjectPlace, 'place/partOfPlace') as $part) {
                    while ($part) {
                        if ($partName = $reader->firstValue($part, 'namePlaceSet/appellationValue')) {
                            $placeNames[] = $partName;
                        }
                        $part = $reader->first($part, 'partOfPlace');
                    }
                }
                $displayPlace = implode(', ', $placeNames);
            }
            if (!$displayPlace) {
                continue;
            }
            if ($extended) {
                $place = [
                    'heading' => [$displayPlace],
                ];
                // Collect all ids but use only the first for type etc:
                $details = [];
                foreach ($reader->all($subjectPlace, 'place/placeID') as $placeId) {
                    $id = $reader->value($placeId);
                    $type = $this->getPlaceIDType($placeId);
                    if ($type && $prependType && !in_array(strtolower($type), $this->uniquePlaceIDTypes)) {
                        $id = "($type)$id";
                    }
                    $typeDesc = $this->translate('place_id_type_' . $type, [], '');
                    if ($typeDesc) {
                        $details[] = $typeDesc;
                    }
                    if (isset($place['type'])) {
                        $place['ids'][] = $id;
                        continue;
                    }
                    $place['type'] = $type;
                    $place['id'] = $id;
                    $place['ids'][] = $id;
                }
                if ($details) {
                    $place['detail'] = implode(', ', $details);
                }
                $results[] = $place;
            } else {
                $results[] = $displayPlace;
            }
        }
        return $results;
    }

    /**
     * Get extended subject places.
     *
     * @return array
     */
    public function getSubjectPlacesExtended(): array
    {
        return $this->getSubjectPlaces(true);
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        if (isset($this->cache[__FUNCTION__])) {
            return $this->cache[__FUNCTION__];
        }
        $this->cache[__FUNCTION__] = array_merge($this->getAudios(), $this->getVideos());
        return $this->cache[__FUNCTION__];
    }

    /**
     * Get the web resource links from the record.
     *
     * @return array
     */
    public function getWebResources(): array
    {
        $reader = $this->getXmlReader();
        $path = 'lido/descriptiveMetadata/objectRelationWrap/relatedWorksWrap/relatedWorkSet/relatedWork';
        $results = [];
        $language = $this->getLocale();
        foreach ($reader->all(path: $path) as $work) {
            $url = $this->getLanguageSpecificValueByPath($work, 'object/objectWebResource', $language);
            if (!$url || $this->urlBlocked($url)) {
                continue;
            }
            $result = [
                'url' => $url,
            ];
            if ('' !== ($desc = $this->getLanguageSpecificValueByPath($work, 'displayObject', $language))) {
                $result['desc'] = $desc;
            }
            if ($objectIDs = $reader->all($work, 'object/objectID')) {
                if (!($objectID = $this->getLanguageSpecificNode($objectIDs, $language))) {
                    continue;
                }
                $result['info'] = $reader->value($objectID) ?? '';
                if ($label = $reader->attr($objectID, 'label')) {
                    $result['label'] = $label;
                }
            }
            $results[] = $result;
        }
        return $results;
    }

    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     * objects are normally constructed by Record Driver objects using data
     * passed in from a Search Results object. The exact nature of the data may
     * vary depending on the data source -- the important thing is that the
     * Record Driver + Search Results objects work together correctly.
     *
     * @return void
     */
    public function setRawData($data)
    {
        parent::setRawData($data);
    }

    /**
     * Does a record come from a source that has given data
     * source specific configuration set as true?
     *
     * @param string $confParam string of configuration parameter name
     *
     * @return bool
     */
    protected function getDataSourceConfigurationValue($confParam)
    {
        $datasource = $this->getDataSource();
        return isset($this->recordConfig->$confParam)
            && isset($this->recordConfig->$confParam[$datasource])
            ? $this->recordConfig->$confParam[$datasource] : null;
    }

    /**
     * Get identifiers by type.
     *
     * @param bool  $includeType Whether to include identifier type in parenthesis
     * @param array $include     Type and label attributes to include
     * @param array $exclude     Type and label attributes to exclude
     *
     * @return array
     */
    protected function getIdentifiersByType(
        bool $includeType,
        array $include = [],
        array $exclude = []
    ): array {
        $reader = $this->getXmlReader();
        $results = [];
        $path = 'lido/descriptiveMetadata/objectIdentificationWrap/repositoryWrap/repositorySet/workID';
        foreach ($reader->all(path: $path) as $workID) {
            if ('' === ($identifier = $reader->value($workID) ?? '')) {
                continue;
            }
            $label = $reader->attr($workID, 'label') ?? '';
            $type = $reader->attr($workID, 'type') ?? '';
            $typesLC = [$this->toLower($label), $this->toLower($type)];
            if (
                ($include && !array_intersect($typesLC, $include))
                || ($exclude && array_intersect($typesLC, $exclude))
            ) {
                continue;
            }
            // Never display type if value is URI
            $displayType = preg_match('/^http?:/', $type) ? '' : $type;
            if (($label || $displayType) && $includeType) {
                $identifier .= ' (' . ($label ?: $displayType) . ')';
            }
            $results[] = $identifier;
        }
        return $results;
    }

    /**
     * Get physical locations.
     *
     * @return array
     */
    public function getPhysicalLocations(): array
    {
        $result = $this->getPhysicalLocationsExtended();
        return array_column($result, 'location');
    }

    /**
     * Get physical locations and additional information.
     *
     * Returns a multidimensional array containing arrays with keys:
     *  - 'location'        string  Physical location
     *  - 'locationInfo'    array   Additional information
     *  - 'locationAsLink'  bool    If location should be a link
     *
     * @return array
     */
    public function getPhysicalLocationsExtended(): array
    {
        $reader = $this->getXmlReader();
        $results = [];
        $path = 'lido/descriptiveMetadata/objectIdentificationWrap/repositoryWrap/repositorySet';

        foreach ($reader->all(path: $path) as $repository) {
            $type = $this->toLower($reader->attr($repository, 'type') ?? '');
            if (!in_array($type, ['current location', 'http://terminology.lido-schema.org/lido01018'])) {
                continue;
            }
            $locations = [];
            foreach ($reader->all($repository, 'repositoryLocation/namePlaceSet') as $nameSet) {
                if ('' !== ($name = $reader->firstValue($nameSet, 'appellationValue') ?? '')) {
                    $locations[] = $name;
                }
            }
            foreach ($reader->all($repository, 'repositoryLocation/partOfPlace') as $part) {
                while ($part) {
                    if ('' !== ($partName = $reader->firstValue($part, 'namePlaceSet/appellationValue') ?? '')) {
                        $locations[] = $partName;
                    }
                    $part = $reader->first($part, 'partOfPlace');
                }
            }
            if ($locations) {
                $locationInfo = [];
                foreach ($reader->all($repository, 'repositoryLocation/placeID') as $placeId) {
                    if (!($placeIdStr = $reader->value($placeId))) {
                        continue;
                    }
                    $idType = $this->getPlaceIDType($placeId);
                    $prependType = $idType !== '' && !in_array(strtolower($idType), $this->uniquePlaceIDTypes);
                    $id = $prependType ? "($idType)$placeIdStr" : $placeIdStr;
                    $locationInfo['ids'][] = $id;
                }
                $results[] = [
                    'location' => implode(', ', $locations),
                    'locationInfo' => $locationInfo,
                    'locationAsLink' => true,
                ];
            }
            $lang = $this->getLocale();
            if ($display = $this->getLanguageSpecificValueByPath($repository, 'displayRepository', $lang)) {
                $results[] = [
                    'location' => $display,
                    'locationInfo' => [],
                    'locationAsLink' => false,
                ];
            }
        }
        return $results;
    }

    /**
     * Get a language-specific node from an array of nodes.
     *
     * @param array  $nodeList Nodes to look in
     * @param string $language Language to look for
     *
     * @return ?array
     */
    protected function getLanguageSpecificNode(array $nodeList, string $language): ?array
    {
        $languages = [];
        if ($language) {
            $languages[] = $language;
            // Add region-less language if needed:
            $parts = explode('-', $language, 2);
            if (isset($parts[1])) {
                $languages[] = $parts[0];
            }
        }

        $firstNode = null;
        $reader = $this->getXmlReader();
        foreach ($languages as $lng) {
            foreach ($nodeList as $node) {
                $contents = $reader->value($node);
                if (null === $firstNode && '' !== $contents) {
                    $firstNode = $node;
                }
                $langAttr = $reader->attr($node, "{{$this->xmlNs}}lang") ?? $reader->attr($node, 'lang');
                if ($langAttr === $lng && '' !== $contents) {
                    return $node;
                }
            }
        }
        // Return first non-empty node if available or first node otherwise, or null if there are no nodes:
        return $firstNode ?? $nodeList[0] ?? null;
    }

    /**
     * Get a language-specific value from an array of nodes.
     *
     * @param array  $nodeList Nodes to look in
     * @param string $language Language to look for
     *
     * @return string
     */
    protected function getLanguageSpecificValue(array $nodeList, string $language): string
    {
        $node = $this->getLanguageSpecificNode($nodeList, $language);
        return $node ? $this->getXmlReader()->value($node) : '';
    }

    /**
     * Get a language-specific value from an array of nodes.
     *
     * @param ?array $node     Parent node
     * @param string $path     Element path
     * @param string $language Language to look for
     *
     * @return string
     */
    protected function getLanguageSpecificValueByPath(?array $node, string $path, string $language): string
    {
        $reader = $this->getXmlReader();
        if (!($nodes = $reader->all($node, $path))) {
            return '';
        }
        return $this->getLanguageSpecificValue($nodes, $language);
    }

    /**
     * Get all fitting language-specific nodes from a node list.
     *
     * @param array  $nodeList Array of nodes to look in
     * @param string $language Language to look for
     * @param bool   $useFirst Use first appearing language as fallback, otherwise returns all items
     *
     * @return array
     */
    protected function getAllLanguageSpecificNodes(
        array $nodeList,
        string $language,
        bool $useFirst = false
    ): array {
        $languages = [];
        $items = [];
        $allItems = [];
        if ($language) {
            $languages[] = $language;
            if (strlen($language) > 2) {
                $languages[] = substr($language, 0, 2);
            }
        }
        $first = '';
        $reader = $this->getXmlReader();
        foreach ($nodeList as $node) {
            if ('' !== ($reader->value($node))) {
                $lang = $reader->attr($node, 'lang') ?? 'no_locale';
                $first = $first ?: $lang;
                if (in_array($lang, $languages)) {
                    $items[] = $node;
                } elseif (!$useFirst || $lang === $first) {
                    $allItems[] = $node;
                }
            }
        }
        // Return either language specific results or all given items.
        return $items ?: $allItems;
    }

    /**
     * Get all fitting language-specific values from a node list.
     *
     * @param array  $nodeList Array of nodes to look in
     * @param string $language Language to look for
     * @param bool   $useFirst Use first appearing language as fallback, otherwise returns all items
     *
     * @return array
     */
    protected function getAllLanguageSpecificValues(
        array $nodeList,
        string $language,
        bool $useFirst = false
    ): array {
        $reader = $this->getXmlReader();
        return array_map(
            [$reader, 'value'],
            $this->getAllLanguageSpecificNodes($nodeList, $language, $useFirst)
        );
    }

    /**
     * Get the displaysubject and description info to summary.
     *
     * @return array $results with summary from displaySubject or description field
     */
    public function getSummary()
    {
        $collected = [];
        $descriptions = [];
        $descriptionsTyped = [];
        $descriptionsUntyped = [];
        $subjectsLabeled = [];
        $subjectsUnlabeled = [];
        $language = $this->getLocale();
        // Collect all fitting description objects
        $reader = $this->getXmlReader();
        $path = 'lido/descriptiveMetadata/objectIdentificationWrap/objectDescriptionWrap/objectDescriptionSet';
        foreach ($reader->all(path: $path) as $node) {
            $type = $reader->attr($node, 'type');
            // Descriptions divided to typed and untyped
            foreach ($reader->all($node, 'descriptiveNoteValue') as $desc) {
                if (!$type) {
                    $descriptionsUntyped[] = $desc;
                } elseif ($type === 'description') {
                    $descriptionsTyped[] = $desc;
                }
            }
        }
        // Collect all fitting subject objects
        $path = 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/subjectSet/displaySubject';
        foreach ($reader->all(path: $path) as $subj) {
            $label = $reader->attr($subj, 'label');
            // Subjects divided to labeled and unlabeled
            if (!$label) {
                $subjectsUnlabeled[] = $subj;
            } elseif ($label === 'aihe') {
                $subjectsLabeled[] = $subj;
            }
        }
        // Check for matching language variations
        if ($descriptionsTyped ?: $descriptionsUntyped) {
            $collectedDesc = $this->getAllLanguageSpecificValues(
                $descriptionsTyped ?: $descriptionsUntyped,
                $language
            );
            //Discard values matching the object title
            $collected = $this->compareWithTitle($collectedDesc);
            foreach ($collected as $item) {
                $descriptions[] = (string)$item;
            }
        }
        if ($subjectsLabeled ?: $subjectsUnlabeled) {
            $collectedSubj = $this->getAllLanguageSpecificValues(
                $subjectsLabeled ?: $subjectsUnlabeled,
                $language
            );
            $collected = $this->compareWithTitle($collectedSubj);
            foreach ($collected as $item) {
                $descriptions[] = (string)$item;
            }
        }
        $descriptions = array_unique($descriptions);

        // Collect all titles to be checked
        $titleNodes = [];
        $titlesNotInDesc = [];
        $displayTitle = $this->getTitle();
        $path = 'lido/descriptiveMetadata/objectIdentificationWrap/titleWrap/titleSet/appellationValue';
        foreach ($reader->all(path: $path) as $title) {
            $pref = $reader->attr($title, 'pref');
            $titleNodes[] = $title;
            if (in_array($pref, $this->preferredTitleLabels) || in_array($pref, $this->alternativeTitleLabels)) {
                $titlesNotInDesc[] = $reader->value($title);
            }
        }
        foreach ($this->getAlternativeTitles() as $title) {
            $titlesNotInDesc[] = $title;
        }
        // Get language specific titles
        if ($titleNodes) {
            $titleValues = $this->getAllLanguageSpecificValues(
                $titleNodes,
                $language,
                true
            );
            // Discard values matching the object title
            $titleValues = $this->compareWithTitle($titleValues);
            // Discard values matching the alternate titles
            if ($titlesNotInDesc) {
                $titleValues = array_diff($titleValues, $titlesNotInDesc);
            }
            // Check for partial titles
            $checkWord = preg_replace('/[^a-zA-Z0-9]/', '', $displayTitle);
            $checkLength = strlen($checkWord);
            foreach ($titleValues as $title) {
                $checkItem = preg_replace('/[^a-zA-Z0-9]/', '', $title);
                if (
                    strncmp($checkItem, $checkWord, $checkLength) == 0
                    && $checkLength !== strlen($checkItem)
                ) {
                    $title = ltrim(substr($title, strlen($displayTitle)), ' .,;:!?');
                    $collectedTitles[] = $title;
                } elseif ($displayTitle !== $title) {
                    $collectedTitles[] = $title;
                }
            }
            // Add titles to the beginning of descriptions
            if (isset($collectedTitles)) {
                $descriptions = array_merge($collectedTitles, $descriptions);
            }
        }

        return $descriptions;
    }

    /**
     * Get introduction.
     *
     * @return array
     */
    public function getIntroduction()
    {
        $results = [];
        $preferredLanguages = $this->getPreferredLanguageCodes();
        $path = 'lido/descriptiveMetadata/objectIdentificationWrap/objectDescriptionWrap/objectDescriptionSet';
        $reader = $this->getXmlReader();
        foreach ($reader->all(path: $path) as $objectDescriptionSet) {
            if ($reader->attr($objectDescriptionSet, 'type') !== 'introduction') {
                continue;
            }
            foreach ($reader->all($objectDescriptionSet, 'descriptiveNoteValue') as $node) {
                if (in_array($reader->attr($node, 'lang'), $preferredLanguages)) {
                    if ('' !== ($term = $reader->value($node))) {
                        $results[] = $term;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Return an XML representation of the record using the specified format.
     * Return false if the format is unsupported.
     *
     * @param string     $format     Name of format to use (corresponds with OAI-PMH
     * metadataPrefix parameter).
     * @param string     $baseUrl    Base URL of host containing VuFind (optional;
     * may be used to inject record URLs into XML when appropriate).
     * @param RecordLink $recordLink Record link helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return mixed         XML, or false if format unsupported.
     */
    public function getXML($format, $baseUrl = null, $recordLink = null)
    {
        if ('oai_lido' === $format) {
            return $this->fields['fullrecord'];
        }
        return parent::getXML($format, $baseUrl, $recordLink);
    }

    /**
     * Get LIDO language codes that correpond user preferred language.
     *
     * @return array
     */
    protected function getPreferredLanguageCodes()
    {
        return self::LANGUAGE_CODES[$this->preferredLanguage]
            ?? self::LANGUAGE_CODES['fi'];
    }

    /**
     * Get the display edition of the current record.
     *
     * @return array
     */
    public function getEditions()
    {
        return $this->getXmlReader()->allValues(
            path: 'lido/descriptiveMetadata/objectIdentificationWrap/displayStateEditionWrap/displayEdition'
        );
    }

    /**
     * Get hierarchy parent links by type.
     *
     * @param string $relatedWorkType Related work type to include, empty string for
     * "others"
     *
     * @return array
     */
    protected function getParentLinksByType(string $relatedWorkType): array
    {
        $allowedTypes = $relatedWorkType
            ? $this->relatedWorkTypeMap[$relatedWorkType] : [];
        $disallowedTypes = $allowedTypes
            ? [] : call_user_func_array(
                'array_merge',
                array_values($this->relatedWorkTypeMap)
            );
        $sourceId = $this->getSource();
        $result = [];
        $reader = $this->getXmlReader();
        $path = 'lido/descriptiveMetadata/objectRelationWrap/relatedWorksWrap/relatedWorkSet';
        foreach ($reader->all(path: $path) as $set) {
            if ('is part of' !== $reader->firstValue($set, 'relatedWorkRelType/term')) {
                continue;
            }
            $workType = '';
            foreach ($reader->all($set, 'relatedWork/object/objectNote') as $note) {
                if ('objectWorkType' === $reader->attr($note, 'type')) {
                    $workType = $this->toLower($reader->value($note));
                    break;
                }
            }
            if (
                ($allowedTypes && !in_array($workType, $allowedTypes))
                || ($disallowedTypes && in_array($workType, $disallowedTypes))
            ) {
                continue;
            }
            if ($id = $reader->firstValue($set, 'relatedWork/object/objectID')) {
                $id = "$sourceId.$id";
            }
            $title = $reader->firstValue($set, 'relatedWork/displayObject');
            if (
                $id
                && $title
                && $id !== $this->getUniqueID()
                && !in_array($id, array_column($result, 'id'))
            ) {
                $result[] = compact('id', 'title');
            }
        }

        return $result;
    }

    /**
     * Get placeID type.
     *
     * @param array $placeID element
     *
     * @return string
     */
    protected function getPlaceIDType(array $placeID): string
    {
        $reader = $this->getXmlReader();
        $type = $reader->attr($placeID, 'type') ?? '';
        if (
            !in_array($this->toLower($type), $this->uniquePlaceIDTypes)
            && $source = $reader->attr($placeID, 'source')
        ) {
            $type = $this->placeIDSourceMappings[$this->toLower($source)] ?? $source;
        }
        return $type;
    }

    /**
     * Get actor's primary ID.
     *
     * @param array $actor Actor
     *
     * @return string
     */
    protected function getPrimaryActorId(array $actor): string
    {
        $reader = $this->getXmlReader();
        $id = '';
        $actorIds = $reader->all($actor, 'actorInRole/actor/actorID') ?: $reader->all($actor, 'actor/actorID');
        foreach ($actorIds as $actorId) {
            if ($actorIdValue = $reader->value($actorId)) {
                if (str_starts_with($actorIdValue, 'http://urn.fi/URN:NBN:fi:au:finaf:')) {
                    return $actorIdValue;
                } elseif (str_starts_with($actorIdValue, 'https://isni.org/isni/')) {
                    // Grab ISNI to return as the fallback value if we can't find a KANTO (finaf) URI:
                    $id = $actorIdValue;
                }
            }
        }
        return $id;
    }

    /**
     * Get XmlDoc from fullrecord.
     *
     * @return XmlDoc
     */
    protected function getXMLReader(): XmlDoc
    {
        $xmlDoc = $this->getXmlDoc();
        $xmlDoc->setDefaultNamespace(static::LIDO_NAMESPACE);
        return $xmlDoc;
    }

    /**
     * Lowercase a string (also handles null).
     *
     * @param ?string $string String
     *
     * @return ?string
     */
    protected function toLower(?string $string): ?string
    {
        return $string ? mb_strtolower($string, 'UTF-8') : $string;
    }
}
