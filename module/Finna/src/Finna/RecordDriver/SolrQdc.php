<?php

/**
 * Model for Qualified Dublin Core records in Solr.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2013-2026.
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
 * @author   Anna Pienimäki <anna.pienimaki@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver;

use FinnaXml\XmlDoc;

use function array_slice;
use function count;
use function in_array;

/**
 * Model for Qualified Dublin Core records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Anna Pienimäki <anna.pienimaki@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrQdc extends \VuFind\RecordDriver\SolrDefault implements \Psr\Log\LoggerAwareInterface
{
    use Feature\SolrFinnaTrait;
    use Feature\FinnaXmlReaderTrait;
    use Feature\FinnaUrlCheckTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Dublin Core XML namespace
     *
     * @var string
     */
    protected string $dcNs = 'http://purl.org/dc/elements/1.1/';

    /**
     * Dublin Core Terms vocabulary namespace
     *
     * @var string
     */
    protected string $dcTermsNs = 'http://purl.org/dc/terms/';

    /**
     * Extended Dublic Core namespace
     *
     * @var string
     */
    protected string $qdcExtendedNs = 'http://www.kansalliskirjasto.fi/qdc_extended';

    /**
     * KK namespace
     *
     * @var string
     */
    protected string $kkNs = 'http://kk/1.0';

    /**
     * Image size mappings
     *
     * @var array
     */
    protected $imageSizeMappings = [
        'thumbnail' => 'small',
        'square' => 'small',
        'small' => 'small',
        'medium' => 'medium',
        'large' => 'large',
        'original' => 'original',
    ];

    /**
     * Image media types
     *
     * @var array
     */
    protected $imageMediaTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Mappings for series information, type => key
     *
     * @var array
     */
    protected $seriesInfoMappings = [
        'ispartofseries' => 'name',
        'numberinseries' => 'partNumber',
    ];

    /**
     * Default value for no_locale definition used for no language
     *
     * @var string
     */
    protected const NO_LOCALE = 'no_locale';

    /**
     * Array of excluded descriptions
     *
     * @var array
     */
    protected $excludedDescriptions = ['notification'];

    /**
     * Constructor
     *
     * @param \VuFind\Config\Config $mainConfig     VuFind main configuration (omit
     * for built-in defaults)
     * @param \VuFind\Config\Config $recordConfig   Record-specific configuration
     * file (omit to use $mainConfig as $recordConfig)
     * @param \VuFind\Config\Config $searchSettings Search-specific configuration
     * file
     */
    public function __construct(
        $mainConfig = null,
        $recordConfig = null,
        $searchSettings = null
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
        $this->searchSettings = $searchSettings;
    }

    /**
     * Return an associative array of abstracts associated with this record
     *
     * @return array of abstracts using abstract languages as keys
     */
    public function getAbstracts()
    {
        $abstracts = [];
        $abstract = '';
        $lang = '';
        $xml = $this->getXmlReader();
        foreach ($this->getDcTermsElements('abstract') as $node) {
            $abstract = $xml->value($node);
            $lang = $this->getLangAttr($node);
            if ($lang == 'en') {
                $lang = 'en-gb';
            }
            $abstracts[$lang] = $abstract;
        }

        return $abstracts;
    }

    /**
     * Get an array of alternative titles for the record.
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        return $this->fields['title_alt'] ?? [];
    }

    /**
     * Get descriptions as an array
     *
     * @return array
     */
    public function getDescriptions(): array
    {
        return $this->getDescriptionsByType();
    }

    /**
     * Get general notes on the record.
     *
     * @return array
     */
    public function getGeneralNotes()
    {
        return $this->getDescriptionsByType(['notification']);
    }

    /**
     * Get an array of mediums for the record
     *
     * @return array
     */
    public function getPhysicalMediums(): array
    {
        return $this->getDcTermsElements('medium', true);
    }

    /**
     * Get an array of formats/extents for the record
     *
     * @return array
     */
    public function getPhysicalDescriptions(): array
    {
        return [...$this->getElements('format', true), ...$this->getDcTermsElements('extent', true)];
    }

    /**
     * Get all authors apart from presenters
     *
     * @return array
     */
    public function getNonPresenterAuthors(): array
    {
        $xml = $this->getXmlReader();
        $authors = [];
        foreach ($this->getPrimaryAuthors() as $author) {
            $authors[] = [
                'name' => $author,
                'role' => 'aut',
            ];
        }
        // Collect oganization names in preferred language
        $organizationTypes = ['organization', 'organisation', 'school', 'faculty', 'department'];
        $organization = [];
        foreach ($this->getElements('contributor') as $contributor) {
            if (!($name = $xml->value($contributor))) {
                continue;
            }
            $role = $this->getTypeAttr($contributor);
            $lang = $this->getLangAttr($contributor) ?? self::NO_LOCALE;
            if ($lang === '-') {
                $lang = self::NO_LOCALE;
            }
            if (in_array($role, $organizationTypes)) {
                $organization[$role][$lang] = $name;
            }
        }
        foreach ($organizationTypes as $orgtype) {
            foreach ($this->getPrioritizedLanguages([], self::NO_LOCALE) as $l) {
                if ($organization[$orgtype][$l] ?? '') {
                    $organization[$orgtype]['preferred'] = $organization[$orgtype][$l];
                    continue 2;
                }
            }
        }
        foreach ($this->getElements('contributor') as $contributor) {
            $role = $this->getTypeAttr($contributor);
            if (($name = $xml->value($contributor)) && $role !== 'orcid') {
                // For organization fields, include only the name in preferred language
                if (in_array($role, $organizationTypes)) {
                    if ($organization[$role]['preferred'] ?? '') {
                        $authors[] = [
                            'name' => $organization[$role]['preferred'],
                            'role' => '',
                        ];
                        $organization[$role]['preferred'] = '';
                    }
                    continue;
                }
                $authors[] = [
                    'name' => $name,
                    'role' => $this->translateRole($role) ?? '',
                ];
            }
        }

        return $authors;
    }

    /**
     * Return an array of image URLs associated with this record with keys:
     * - url         Image URL
     * - description Description text
     * - rights      Rights
     *   - copyright   Copyright (e.g. 'CC BY 4.0') (optional)
     *   - description Human readable description (array)
     *   - link        Link to copyright info
     *
     * @param string $language   Language for copyright information
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return mixed
     */
    public function getAllImages($language = 'fi', $includePdf = true)
    {
        $cacheKey = __FUNCTION__ . "/$language" . ($includePdf ? '/1' : '/0');
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $results = [];
        $rights = [];
        $xml = $this->getXmlReader();
        $thumbnails = [];
        $otherSizes = [];
        $highResolution = [];
        $rights = $this->getRights($language);
        $addToResults = function ($imageData) use (&$results): void {
            if (!$this->maxAmountOfImages()) {
                if (!isset($imageData['urls']['small'])) {
                    $imageData['urls']['small'] = $imageData['urls']['medium']
                        ?? $imageData['urls']['large']
                        ?? $imageData['urls']['original'];
                }
                $imageData = $this->ensureImageSizes($imageData);
                $imageData['downloadable'] = $this->allowRecordImageDownload($imageData);
                $results[] = $imageData;
            }
            $this->imagesCount++;
        };

        $pdfUrl = null;
        foreach ($this->getKkElements('file') as $node) {
            $type = $xml->attr($node, 'type');
            $url = $xml->attr($node, 'href') ?? $xml->value($node);
            $bundle = strtolower($xml->attr($node, 'bundle') ?? '');
            // Store PDFs for use later if images are not found:
            if (null === $pdfUrl && 'original' === $bundle) {
                if (
                    (!$type || 'application/pdf' === $type)
                    || (!$type && preg_match('/\.pdf$/i', $url))
                ) {
                    $pdfUrl = $url;
                }
            }
            if (
                ($type && !in_array($type, array_keys($this->imageMediaTypes)))
                || (!$type && !preg_match('/\.(jpg|png)$/i', $url))
            ) {
                continue;
            }
            if (!$this->isUrlLoadable($url, $this->getUniqueID())) {
                continue;
            }

            if ($bundle === 'thumbnail' && !$otherSizes) {
                // Lets see if the record contains only thumbnails
                $thumbnails[] = $url;
            } else {
                // QDC has no way of telling how to link
                // images so take only first in this situation
                $size = $this->imageSizeMappings[$bundle] ?? false;
                if ($size && !isset($otherSizes[$size])) {
                    if (in_array($size, ['master', 'original'])) {
                        $currentHiRes = [
                            'data' => [],
                            'url' => $url,
                            'format' => $this->imageMediaTypes[$type] ?? 'jpg',
                        ];
                        $highResolution[$size][] = $currentHiRes;
                    }
                    $otherSizes[$size] = $url;
                }
            }
        }

        if ($thumbnails && !$otherSizes) {
            foreach ($thumbnails as $url) {
                $addToResults(
                    [
                        'urls' => ['large' => $url],
                        'description' => '',
                        'rights' => $rights,
                    ]
                );
            }
        } elseif ($otherSizes) {
            $addToResults(
                [
                    'urls' => $otherSizes,
                    'description' => '',
                    'rights' => $rights,
                    'highResolution' => $highResolution,
                ]
            );
        }
        $thumbnails = [];
        $otherSizes = [];
        // Add any PDF if we don't have images:
        if (!$results && $includePdf && $pdfUrl) {
            $addToResults(
                [
                    'urls' => [
                        'large' => $pdfUrl,
                        'small' => $pdfUrl,
                    ],
                    'description' => '',
                    'rights' => $rights,
                    'pdf' => true,
                ]
            );
        }
        return $this->cache[$cacheKey] = $results;
    }

    /**
     * Get image rights
     *
     * @param string $language Language for the copyright
     *
     * @return array [copyright, link, description = []]
     */
    protected function getRights(string $language): array
    {
        $xml = $this->getXmlReader();
        $result = [
            'copyright' => '',
            'link' => '',
            'description' => [],
        ];
        $firstElementPriority = null;
        $cache = [];
        // Get all the copyrights and save them in an array identified by language.
        foreach ($this->getElements('rights') as $right) {
            $type = $this->getTypeAttr($right) ?? '';
            $rightLanguage = $this->getLangAttr($right);
            // QDC sometimes marks languageless elements with a dash
            if (!$rightLanguage || '-' === $rightLanguage) {
                $rightLanguage = self::NO_LOCALE;
            }
            // If no type and language is set and it is the first rights element,
            // then we can assume it is the main copyright to display
            if (null === $firstElementPriority) {
                $firstElementPriority = !$type && self::NO_LOCALE === $rightLanguage;
            }
            $cache[$rightLanguage][] = [
                'txt' => $xml->value($right),
                'type' => $type,
            ];
        }
        if (empty($cache)) {
            return $result;
        }
        // Check that there is proper values to use for displaying the rights.
        $localizedRights = [];
        foreach ($this->getPrioritizedLanguages([$language], self::NO_LOCALE) as $lang) {
            if (empty($cache[$lang])) {
                continue;
            }
            // Check if there is an rights element with priority.
            $localizedRights = (self::NO_LOCALE !== $lang && $firstElementPriority)
                ? array_merge($cache[self::NO_LOCALE], $cache[$lang])
                : $cache[$lang];
            break;
        }
        if (empty($localizedRights)) {
            return $result;
        }
        // Try to get the main copyright to display, normally the first in array.
        $priorityRight = array_shift($localizedRights);
        $mappedRight = $this->getMappedRights($priorityRight['txt']);
        $result['copyright'] = $mappedRight;
        $result['link'] = $this->getRightsLink($mappedRight, $language);
        foreach ($localizedRights as $right) {
            // Add rights as descriptions which have the same localization
            // as the primary right.
            if (
                'copyright' === $right['type']
                && $result['copyright'] !== $right['txt']
            ) {
                $result['description'][] = $right['txt'];
            }
        }
        return $result;
    }

    /**
     * Return education programs
     *
     * @return array
     */
    public function getEducationPrograms()
    {
        return $this->getQdcExtendedElements('programme', true);
    }

    /**
     * Get human readable publication dates for display purposes (may not be suitable
     * for computer processing -- use getPublicationDates() for that).
     *
     * @return array
     */
    public function getHumanReadablePublicationDates()
    {
        if ($dates = $this->getPublicationDateRange()) {
            return [implode('–', $dates)];
        }
        return [];
    }

    /**
     * Get publication date or date range.
     *
     * @return ?array Array of one or two dates or null if not available.
     * If date range is still continuing end year will be an empty string.
     */
    public function getPublicationDateRange()
    {
        return $this->getDateRange('publication');
    }

    /**
     * Return full record as a filtered XmlDoc for public APIs.
     *
     * @return XmlDoc
     *
     * @todo Return XML as string or XmlDoc when all classes support it
     */
    public function getFilteredXmlElement(): XmlDoc
    {
        // Try to filter out any summary or abstract fields
        $filterTerms = [
            'tiivistelmä', 'abstract', 'abstracts', 'abstrakt', 'sammandrag',
            'sommario', 'summary', 'аннотация',
        ];
        // Create new doc directly to avoid default namespace handling:
        $xml = (new XmlDoc())->parse($this->fields['fullrecord']);
        $xml->filter(
            function ($node, $path) use ($xml, $filterTerms) {
                if (in_array($path, ['{}abstract', "{{$this->dcNs}}abstract", "{{$this->dcTermsNs}}abstract"])) {
                    return true;
                }
                if (
                    in_array($path, ['{}description', "{{$this->dcNs}}description", "{{$this->dcTermsNs}}description"])
                ) {
                    $description = mb_strtolower($xml->value($node), 'UTF-8');
                    $firstWords = array_slice(preg_split('/\s/', $description), 0, 5);
                    return (bool)array_intersect($firstWords, $filterTerms);
                }
                return false;
            }
        );
        return $xml;
    }

    /**
     * Return full record as a filtered SimpleXMLElement for public APIs.
     * Legacy method, use getFilteredXmlElement instead.
     *
     * @return \SimpleXMLElement
     */
    public function getFilteredXMLElementLegacy(): \SimpleXMLElement
    {
        $record = clone $this->getXmlRecord();
        while ($record->abstract) {
            unset($record->abstract[0]);
        }
        // Try to filter out any summary or abstract fields
        $filterTerms = [
            'tiivistelmä', 'abstract', 'abstracts', 'abstrakt', 'sammandrag',
            'sommario', 'summary', 'аннотация',
        ];
        for ($i = count($record->description) - 1; $i >= 0; $i--) {
            $node = $record->description[$i];
            $description = mb_strtolower((string)$node, 'UTF-8');
            $firstWords = array_slice(preg_split('/\s/', $description), 0, 5);
            if (array_intersect($firstWords, $filterTerms)) {
                unset($record->description[$i]);
            }
        }

        return $record;
    }

    /**
     * Return full record as filtered XML for public APIs.
     *
     * @return string
     */
    public function getFilteredXML()
    {
        return $this->getFilteredXmlElement()->toXML();
    }

    /**
     * Get identifier
     *
     * @return array
     */
    public function getIdentifier()
    {
        $xml = $this->getXmlReader();
        foreach ($this->getElements('identifier') as $identifier) {
            // Inventory number
            if ($this->getTypeAttr($identifier) === 'wikidata:P217') {
                return [$xml->value($identifier)];
            }
        }
        return [];
    }

    /**
     * Get identifiers as an array
     *
     * @return array
     */
    public function getOtherIdentifiers(): array
    {
        $results = [];
        $xml = $this->getXmlReader();
        foreach ([...$this->getElements('identifier'), ...$this->getDcTermsElements('isFormatOf')] as $identifier) {
            $type = $this->getTypeAttr($identifier) ?? '';
            if (in_array($type, ['issn', 'isbn'])) {
                continue;
            }
            $identifierTrimmed = $xml->value($identifier);
            $dashless = str_replace('-', '', $identifierTrimmed);
            // ISBN
            if (preg_match('{^[0-9]{9,12}[0-9xX]}', $dashless)) {
                continue;
            }
            // ISSN
            if (preg_match('{(issn:)[\S]{4}\-[\S]{4}}', $identifierTrimmed)) {
                continue;
            }

            // Leave out some obvious matches like urls or urns
            if (!preg_match('{(^urn:|^https?)}i', $identifierTrimmed)) {
                $detail = $type;
                $data = $identifierTrimmed;
                $results[] = compact('data', 'detail');
            }
        }
        return $results;
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs()
    {
        $result = [];
        $xml = $this->getXmlReader();
        foreach ([...$this->getElements('identifier'), ...$this->getDcTermsElements('isFormatOf')] as $identifier) {
            $identifierStr = $xml->value($identifier);
            $trimmed = str_replace('-', '', $identifierStr);
            if (
                $this->getTypeAttr($identifier) === 'isbn'
                || preg_match('{^[0-9]{9,12}[0-9xX]}', $trimmed)
            ) {
                $result[] = $identifierStr;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * Get all record links related to the current record. Each link is returned as
     * array.
     * Format:
     * array(
     *        array(
     *               'title' => label_for_title
     *               'value' => link_name
     *               'link'  => link_URI
     *        ),
     *        ...
     * )
     *
     * @return null|array
     */
    public function getAllRecordLinks()
    {
        $xml = $this->getXmlReader();
        $relations = [];
        foreach ($this->getDcTermsElements('isPartOf', true) as $isPartOf) {
            $relations[] = [
                'value' => $isPartOf,
                'link' => [
                    'value' => $isPartOf,
                    'type' => 'allFields',
                ],
            ];
        }
        foreach ($this->getElements('relation') as $relation) {
            if ('ispartof' === $this->getTypeAttr($relation)) {
                $value = $xml->value($relation);
                $relations[] = [
                    'value' => $value,
                    'link' => [
                        'value' => $value,
                        'type' => 'allFields',
                    ],
                ];
            }
        }
        return $relations;
    }

    /**
     * Return keywords
     *
     * @return array
     */
    public function getKeywords()
    {
        return $this->getQdcExtendedElements('keyword', true);
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
        $urls = [];
        foreach (parent::getURLs() as $url) {
            if (!$this->urlBlocked($url['url'] ?? '')) {
                if (!$this->maxAmountOfURLs()) {
                    $urls[] = $url;
                }
                $this->urlsCount++;
            }
        }
        $urls = $this->resolveUrlTypes($urls);
        return $urls;
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
        if ('oai_qdc' === $format) {
            return $this->fields['fullrecord'];
        }
        return parent::getXML($format, $baseUrl, $recordLink);
    }

    /**
     * Get series information
     *
     * @return array
     */
    public function getSeries(): array
    {
        $xml = $this->getXmlReader();
        $locale = $this->getLocale();
        $results = [];
        foreach ($this->getElements('relation') as $relation) {
            $type = $this->getTypeAttr($relation);
            if ($key = $this->seriesInfoMappings[$type] ?? false) {
                $lang = $this->getLangAttr($relation) ?: 'nolocale';
                // Initialize the result so that it contains the required elements:
                if (!isset($results[$lang])) {
                    $results[$lang] = [
                        'name' => '',
                    ];
                }
                if (empty($results[$lang][$key])) {
                    $results[$lang][$key] = $xml->value($relation);
                }
            }
        }

        return isset($results[$locale])
            ? [$results[$locale]]
            : array_values($results);
    }

    /**
     * Get access rights
     *
     * @return array
     */
    public function getAccessRestrictions(): array
    {
        $xml = $this->getXmlReader();
        $locale = $this->getLocale();
        $primary = [];
        $all = [];
        foreach ($this->getElements('rights') as $right) {
            if ('accessrights' === $this->getTypeAttr($right)) {
                $value = $xml->value($right);
                $all[] = $value;
                $rightLanguage = $this->getLangAttr($right);
                if (!$rightLanguage || $rightLanguage === $locale) {
                    $primary[] = $value;
                }
            }
        }
        return $primary ?: $all;
    }

    /**
     * Get descriptions by type
     *
     * @param array $include Description types to include, otherwise all but excluded types
     *
     * @return array
     */
    protected function getDescriptionsByType(array $include = []): array
    {
        $xml = $this->getXmlReader();
        $descriptions = [];
        $first = '';
        $exclude = $include ? [] : $this->excludedDescriptions;
        foreach ($this->getElements('description') as $description) {
            $type = $this->getTypeAttr($description);
            if (($include && !in_array($type, $include)) || ($exclude && in_array($type, $exclude))) {
                continue;
            }
            if (($format = $xml->attr($description, 'format')) && str_starts_with($format, 'image/')) {
                continue;
            }
            if ($trimmed = $xml->value($description)) {
                $lang = $this->getLangAttr($description) ?? self::NO_LOCALE;
                $first = $first ?: $lang;
                $descriptions[$lang][] = $trimmed;
            }
        }
        if ($descriptions) {
            foreach ($this->getPrioritizedLanguages() as $l) {
                if ($descriptions[$l] ?? []) {
                    return $descriptions[$l];
                }
            }
            return $descriptions[$first];
        }
        return [];
    }

    /**
     * Given a Solr field name, return an appropriate caption.
     *
     * @param string $field Solr field name
     *
     * @return mixed        Caption if found, false if none available.
     */
    public function getSnippetCaption($field)
    {
        return $field !== 'contents' ? parent::getSnippetCaption($field) : false;
    }

    /**
     * Get contributor role translation key
     *
     * @param string $role     Contributor role
     * @param string $fallback Fallback to use when no supported role is found
     *
     * @return ?string Translation key
     */
    protected function translateRole($role, $fallback = null): ?string
    {
        // Map contributor role to CreatorRole translations
        $roleMap = [
            'actor' => 'act',
            'advisor' => 'ths',
            'animator' => 'anm',
            'artist' => 'art',
            'audioassistant' => 'Audio assistant',
            'audioeditor' => 'Sound editor',
            'audioengineer' => 'aue',
            'author' => 'aut',
            'cameraoperator' => 'cop',
            'casting' => 'cad',
            'choreographer' => 'chr',
            'cinematographer' => 'cng',
            'composer' => 'cmp',
            'conceptor' => 'ccp',
            'conductor' => 'cnd',
            'consultant' => 'csl',
            'contributor' => 'ctb',
            'copyrightholder' => 'cph',
            'costumedesigner' => 'cst',
            'dancer' => 'dnc',
            'degreeSupervisor' => 'dgs',
            'degreesupervisor' => 'dgs',
            'director' => 'drt',
            'distributor' => 'dst',
            'editor' => 'edt',
            'engineer' => 'eng',
            'filmeditor' => 'flm',
            'filmmaker' => 'fmk',
            'funder' => 'fnd',
            'groupauthor' => 'aut',
            'illustrator' => 'ill',
            'instrumentalist' => 'itr',
            'interviewee' => 'ive',
            'interviewer' => 'ivr',
            'lightingdesigner' => 'lgd',
            'makeupartist' => 'mka',
            'musicaldirector' => 'msd',
            'musician' => 'mus',
            'narrator' => 'nrt',
            'opponent' => 'opn',
            'organizer' => 'orm',
            'other' => 'oth',
            'performer' => 'prf',
            'photographer' => 'pht',
            'producer' => 'pro',
            'productioncompany' => 'prn',
            'productionmanager' => 'pmn',
            'productionpersonnel' => 'prd',
            'recordist' => 'rcd',
            'researcher' => 'res',
            'reviewer' => 'dgc',
            'setdesigner' => 'std',
            'singer' => 'sng',
            'sounddesigner' => 'sds',
            'speaker' => 'spk',
            'specialeffectsprovider' => 'sfx',
            'supervisor' => 'dgs',
            'technicaldirector' => 'tcd',
            'thesisadvisor' => 'ths',
            'translator' => 'trl',
            'visualeffectsprovider' => 'vfx',
            'vocalist' => 'voc',
            'voiceactor' => 'vac',
            'writer' => 'rda:writer',
        ];
        return $roleMap[$role] ?? $fallback;
    }

    /**
     * Get XmlDoc from fullrecord.
     *
     * @return XmlDoc
     */
    protected function getXMLReader(): XmlDoc
    {
        $xmlDoc = $this->getXmlDoc();
        $xmlDoc->setDefaultNamespace($this->dcNs, 'dc');
        return $xmlDoc;
    }

    /**
     * Get elements from the terms or elements namespaces with fallback to default namespace.
     *
     * @param string $nodeName   Node name
     * @param bool   $valuesOnly Return only values?
     *
     * @return array
     */
    protected function getElements(string $nodeName, bool $valuesOnly = false): array
    {
        $xml = $this->getXmlReader();
        // Prefer elements in the terms namespace:
        $method = $valuesOnly ? 'allValues' : 'all';
        return $this->getDcTermsElements($nodeName, $valuesOnly)
            ?: $xml->$method(path: "{{$this->dcNs}}$nodeName");
    }

    /**
     * Get elements from the DcTerms namespace with fallback to default namespace.
     *
     * @param string $nodeName   Node name
     * @param bool   $valuesOnly Return only values?
     *
     * @return array
     */
    protected function getDcTermsElements(string $nodeName, bool $valuesOnly = false): array
    {
        $xml = $this->getXmlReader();
        $method = $valuesOnly ? 'allValues' : 'all';
        return $xml->$method(path: "{{$this->dcTermsNs}}$nodeName") ?: $xml->$method(path: $nodeName);
    }

    /**
     * Get elements from the QdcExtended namespace with fallback to default namespace.
     *
     * @param string $nodeName   Node name
     * @param bool   $valuesOnly Return only values?
     *
     * @return array
     */
    protected function getQdcExtendedElements(string $nodeName, bool $valuesOnly = false): array
    {
        $xml = $this->getXmlReader();
        $method = $valuesOnly ? 'allValues' : 'all';
        return $xml->$method(path: "{{$this->qdcExtendedNs}}$nodeName") ?: $xml->$method(path: $nodeName);
    }

    /**
     * Get elements from the KK namespace with fallback to default namespace.
     *
     * @param string $nodeName Node name
     *
     * @return array
     */
    protected function getKkElements(string $nodeName): array
    {
        $xml = $this->getXmlReader();
        return $xml->all(path: "{{$this->kkNs}}$nodeName");
    }

    /**
     * Get type attribute.
     *
     * @param array $node Node
     *
     * @return ?string
     */
    protected function getTypeAttr(array $node): ?string
    {
        return $this->getXmlReader()->attr($node, 'type');
    }
}
