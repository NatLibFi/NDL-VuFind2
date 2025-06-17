<?php

/**
 * Collection RecordDataFormatter specs.
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */

namespace Finna\RecordDataFormatter\Specs\Utils;

use Finna\RecordDataFormatter\Specs\DefaultRecord;

use function in_array;
use function is_array;

/**
 * Collection RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
trait RecordFieldsTrait
{
    /**
     * Record fields with single template lines
     *
     * @var array
     */
    public array $singleTemplateLines = [
    'Genre' => [
    'getGenres',
    'data-genres.phtml',
    [
        'context' => [
            'class' => 'recordGenres',
        ],
    ],
    ],
    'Age Limit' => [
    'getAgeLimit',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordAgeLimit',
        ],
    ],
    ],
    'Original Work' => [
    'getOriginalWork',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'recordOriginalWork',
        ],
    ],
    ],
    'Published in' => [
    'getContainerTitle',
    'data-containerTitle.phtml',
    [
        'context' => [
            'class' => 'record-container-link',
        ],
    ],
    ],
    'New Title' => [
    'getNewerTitles',
    'data-titles.phtml',
    [
        'context' => [
            'class' => 'recordNextTitles',
        ],
    ],
    ],
    'Previous Title' => [
    'getPreviousTitles',
    'data-titles.phtml',
    [
        'context' => [
            'class' => 'recordPrevTitles',
        ],
    ],
    ],
    'Secondary Authors' => [
    'getNonPresenterSecondaryAuthors',
    'data-contributors.phtml',
    [
        'context' => [
            'class' => 'recordAuthors',
        ],
        'labelFunction' => [DefaultRecord::class, 'secondaryAuthorsLabel',],
    ],
    ],
    'Actors' => [
    'getPresenters',
    'data-actors.phtml',
    [
        'context' => [
            'class' => 'recordPresenters',
        ],
    ],
    ],
    'Item Description FWD' => [
    'getGeneralNotes',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'recordDescription',
        ],
    ],
    ],
    'Description FWD' => [
    'getDescription',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'recordDescription',
        ],
    ],
    ],
    'Identifiers' => [
    'getOtherIdentifiers',
    'data-lines-with-detail.phtml',
    [
        'context' => [
            'class' => 'recordIdentifiers',
        ],
    ],
    ],
    'Press Reviews' => [
    'getPressReview',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-press-review',
        ],
    ],
    ],
    'Music' => [
    'getMusicInfo',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-music',
        ],
    ],
    ],
    'Projected Publication Date' => [
    'getProjectedPublicationDate',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'coreProjectedPublicationDate',
        ],
    ],
    ],
    'Dissertation Note' => [
    'getDissertationNote',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'coreDissertationNote',
        ],
    ],
    ],
    'Other Links' => [
    'getOtherLinks',
    'data-getOtherLinks.phtml',
    [
        'labelFunction' => [DefaultRecord::class, 'otherLinksLabel',],
        'context' => [
            'class' => 'recordOtherLink',
        ],
    ],
    ],
    'Presenters' => [
    'getPresenters',
    'data-presenters.phtml',
    [
        'context' => [
            'class' => 'recordPresenters',
        ],
    ],
    ],
    'Presenters Marc' => [
    'getSecondaryPresenters',
    'data-presenters.phtml',
    [
        'context' => [
            'class' => 'recordPresenters',
            'title' => 'Presenters',
        ],
    ],
    ],
    'Other Titles' => [
    'getAlternativeTitles',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordAltTitles',
        ],
    ],
    ],
    'Format' => [
    'getFormats',
    'format-list.phtml',
    [
        'context' => [
            'class' => 'recordFormat',
        ],
    ],
    ],
    'Parent Archive' => [
    'getParentArchives',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordHierarchyLinks',
        ],
    ],
    ],
    'Parent Collection' => [
    'getParentCollections',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordHierarchyLinks',
        ],
    ],
    ],
    'Parent Subcollection' => [
    'getParentSubcollections',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordHierarchyLinks',
        ],
    ],
    ],
    'Parent Series' => [
    'getParentSeries',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordHierarchyLinks',
        ],
    ],
    ],
    'Parent Work' => [
    'getParentWorks',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordHierarchyLinks',
        ],
    ],
    ],
    'Parent Unclassified Entity' => [
    'getParentUnclassifiedEntities',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordHierarchyLinks',
        ],
    ],
    ],
    'Archive Origination' => [
    'getOriginationExtended',
    'data-origination.phtml',
    [
        'context' => [
            'class' => 'record-origination',
        ],
    ],
    ],
    'Archive' => [
    'getParentArchives',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordHierarchyLinks',
        ],
        'labelFunction' => [DefaultRecord::class, 'archiveLabel',],
    ],
    ],
    'Archive Series' => [
    'getParentSeries',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordSeries',
        ],
    ],
    ],
    'Archive File' => [
    'getParentFiles',
    'data-hierarchyLinks.phtml',
    [
        'context' => [
            'class' => 'recordFile',
            'levels' => \Finna\RecordDriver\SolrEad::FILE_LEVELS,
        ],
    ],
    ],
    'Physical Medium' => [
    'getPhysicalMediums',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'physical-medium',
        ],
    ],
    ],
    'Physical Description' => [
    'getPhysicalDescriptions',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'physicalDescriptions',
        ],
    ],
    ],
    'Extent' => [
    'getPhysicalDescriptions',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-extent',
        ],
    ],
    ],
    'Language' => [
    'getLanguages',
    'data-transEscLangcode.phtml',
    [
        'context' => [
            'class' => 'recordLanguage',
        ],
    ],
    ],
    'original_work_language' => [
    'getOriginalLanguages',
    'data-transEscLangcode.phtml',
    [
        'context' => [
            'class' => 'originalLanguage',
        ],
    ],
    ],
    'Language of Abstract' => [
    'getAbstractLanguage',
    'data-transEscLangcode.phtml',
    [
        'context' => [
            'class' => 'abstract-language',
        ],
    ],
    ],
    'Item Notes' => [
    'getGeneralNotes',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordDescription',
        ],
    ],
    ],
    'Local Note' => [
    'getLocalNotes',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-local-note',
        ],
    ],
    ],
    'Organisation' => [
    'getInstitutions',
    'data-organisation.phtml',
    [
        'context' => [
            'class' => 'recordInstitution',
        ],
    ],
    ],
    'Collection' => [
    'getCollections',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordCollection',
        ],
    ],
    ],
    'Content Description' => [
    'getContentDescription',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordContentDescription',
        ],
    ],
    ],
    'Item History' => [
    'getItemHistory',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordHistory',
        ],
    ],
    ],
    'Inventory ID' => [
    'getIdentifier',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordIdentifier',
        ],
    ],
    ],
    'Other ID' => [
    'getLocalIdentifiers',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordIdentifiers',
        ],
    ],
    ],
    'Measurements' => [
    'getMeasurements',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordMeasurements',
        ],
    ],
    ],
    'Inscriptions' => [
    'getInscriptions',
    'data-inscriptions.phtml',
    [
        'context' => [
            'class' => 'recordInscriptions',
        ],
    ],
    ],
    'Other Classification' => [
    'getFormatClassifications',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordClassifications',
        ],
    ],
    ],
    'Unit ID' => [
    'getUnitID',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordReferenceCode',
        ],
    ],
    ],
    'Unit IDs' => [
    'getUnitIds',
    'data-lines-with-detail.phtml',
    ],
    'Authors' => [
    'getNonPresenterAuthors',
    'data-authors.phtml',
    [
        'context' => [
            'class' => 'recordAuthors',
        ],
    ],
    ],
    'Publisher' => [
    'getPublicationDetails',
    'data-publicationDetails.phtml',
    [
        'context' => [
            'class' => 'recordPublications',
        ],
    ],
    ],
    'Published' => [
    'getPublicationDetails',
    'data-publicationDetails.phtml',
    [
        'context' => [
            'class' => 'recordPublications',
        ],
    ],
    ],
    'Edition' => [
    'getEdition',
    'data-edition.phtml',
    [
        'context' => [
            'class' => 'recordEdition',
        ],
    ],
    ],
    'Series' => [
    'getSeries',
    'data-series.phtml',
    [
        'context' => [
            'class' => 'recordSeries',
        ],
    ],
    ],
    'Country of Producing Entity' => [
    'getCountry',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-country',
        ],
    ],
    ],
    'Classification' => [
    'getClassifications',
    'data-classification.phtml',
    [
        'context' => [
            'class' => 'recordClassifications',
        ],
    ],
    ],
    'Dewey Classification' => [
    'getDeweyClassifications',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordClassifications',
        ],
    ],
    ],
    'lido_editions' => [
    'getEditions',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordDisplayEdition',
        ],
    ],
    ],
    'Subject Detail' => [
    'getSubjectDetails',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordSubjects',
        ],
    ],
    ],
    'Subject Place' => [
    'getSubjectPlacesExtended',
    'data-allSubjectHeadingsExtended.phtml',
    [
        'context' => [
            'class' => 'recordSubjects',
            'headingType' => 'place',
        ],
    ],
    ],
    'Subject Date' => [
    'getSubjectDates',
    'data-subjectDate.phtml',
    [
        'context' => [
            'class' => 'recordSubjects',
        ],
    ],
    ],
    'Subject Actor' => [
    'getSubjectActors',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordSubjects',
        ],
    ],
    ],
    'Subjects' => [
    'getAllSubjectHeadings',
    'data-allSubjectHeadings.phtml',
    [
        'context' => [
            'class' => 'recordSubjects',
        ],
    ],
    ],
    'SubjectsWithoutPlaces' => [
    'getAllSubjectHeadingsWithoutPlacesExtended',
    'data-allSubjectHeadingsExtended.phtml',
    [
        'context' => [
            'class' => 'recordSubjects',
            'title' => 'Subjects',
        ],
    ],
    ],
    'subjects_extended' => [
    'getAllSubjectHeadingsExtended',
    'data-allSubjectHeadingsExtended.phtml',
    [
        'context' => [
            'class' => 'recordSubjects',
        ],
    ],
    ],
    'Methodology' => [
    'getMethodology',
    'data-methodology-links.phtml',
    [
        'context' => [
            'class' => 'recordMethodology',
        ],
    ],
    ],
    'Publications' => [
    'getRelatedPublications',
    'data-relatedPublications.phtml',
    [
        'context' => [
            'class' => 'record-related-publications',
        ],
    ],
    ],
    'Other Classifications' => [
    'getOtherClassifications',
    'data-keywords.phtml',
    [
        'context' => [
            'class' => 'recordClassifications',
            'title' => 'Classification',
        ],
    ],
    ],
    'Introduction' => [
    'getIntroduction',
    'data-markdown.phtml',
    [
        'context' => [
            'class' => 'record-introduction',
        ],
    ],
    ],
    'Manufacturer' => [
    'getManufacturer',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'recordManufacturer',
        ],
    ],
    ],
    'Production' => [
    'getProducers',
    'data-producers.phtml',
    [
        'context' => [
            'class' => 'record-production',
        ],
    ],
    ],
    'Production Costs' => [
    'getProductionCost',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-production-cost',
        ],
    ],
    ],
    'Funding' => [
    'getFunders',
    'data-funding.phtml',
    [
        'context' => [
            'class' => 'record-funders',
        ],
    ],
    ],
    'Distribution' => [
    'getDistributors',
    'data-distribution.phtml',
    [
        'context' => [
            'class' => 'record-distributors',
        ],
    ],
    ],
    'Premiere Night' => [
    'getPremiereTime',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-premiere-night',
        ],
    ],
    ],
    'Premiere Theaters' => [
    'getPremiereTheaters',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-premiere-theaters',
        ],
    ],
    ],
    'Broadcasting Dates' => [
    'getBroadcastingInfo',
    'data-broadcasting-dates.phtml',
    [
        'context' => [
            'class' => 'record-broadcasting-info',
        ],
    ],
    ],
    'Number of Viewers' => [
    'getAmountOfViewers',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-number-of-viewers',
        ],
    ],
    ],
    'Film Festivals' => [
    'getFestivalInfo',
    'data-festival-info.phtml',
    [
        'context' => [
            'class' => 'record-festival-info',
        ],
    ],
    ],
    'Foreign Distribution' => [
    'getForeignDistribution',
    'data-foreign-distribution.phtml',
    [
        'context' => [
            'class' => 'record-foreign-distribution',
        ],
    ],
    ],
    'Film Copies' => [
    'getNumberOfCopies',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-film-copies',
        ],
    ],
    ],
    'Other Screenings' => [
    'getOtherScreenings',
    'data-other-screenings.phtml',
    [
        'context' => [
            'class' => 'record-other-screenings',
        ],
    ],
    ],
    'Movie Thanks' => [
    'getMovieThanks',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-thanks',
            'title' => 'movie_thanks',
        ],
    ],
    ],
    'Exterior Images' => [
    'getExteriors',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-exteriors',
        ],
    ],
    ],
    'Interior Images' => [
    'getInteriors',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-interiors',
        ],
    ],
    ],
    'Studios' => [
    'getStudios',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-studios',
        ],
    ],
    ],
    'Filming Location Notes' => [
    'getLocationNotes',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-location-notes',
        ],
    ],
    ],
    'Filming Date' => [
    'getFilmingDate',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-filming-date',
        ],
    ],
    ],
    'Archive Films' => [
    'getArchiveFilms',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'record-archive-films',
        ],
    ],
    ],
    'Additional Information' => [
    'getTitleStatement',
    'data-addInfo.phtml',
    [
        'context' => [
            'class' => 'recordTitleStatement',
        ],
    ],
    ],
    'Additional Information Extended' => [
    'getTitleStatementsExtended',
    'data-addInfoExtended.phtml',
    [
        'context' => [
            'class' => 'recordTitleStatement',
            'title' => 'AdditionalInformation',
        ],
    ],
    ],
    'child_records' => [
    'getChildRecordCount',
    'data-childRecords.phtml',
    [
        'allowZero' => '',
        'context' => [
            'class' => 'recordComponentParts',
        ],
    ],
    ],
    'Record Links' => [
    'getAllRecordLinks',
    'data-allRecordLinks.phtml',
    [
        'context' => [
            'class' => 'recordLinks',
            'title' => '',
        ],
    ],
    ],
    'Related Materials' => [
    'getAllRecordLinks',
    'data-allRecordLinks.phtml',
    [
        'context' => [
            'class' => 'relatedMaterials',
        ],
    ],
    ],
    'Online Access' => [
    true,
    'data-onlineAccess.phtml',
    [
        'context' => [
            'class' => 'webResource',
        ],
    ],
    ],
    'Source Collection' => [
    'getSource',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordSource',
        ],
    ],
    ],
    'Publish date' => [
    'getDateSpan',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extendedDateSpan',
        ],
    ],
    ],
    'Keywords' => [
    'getKeywords',
    'data-keywords.phtml',
    [
        'context' => [
            'class' => 'record-keywords',
        ],
    ],
    ],
    'Education Programs' => [
    'getEducationPrograms',
    'data-education.phtml',
    [
        'context' => [
            'class' => 'record-education-programs',
        ],
    ],
    ],
    'Educational Role' => [
    'getEducationalAudiences',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-educational-audience',
        ],
    ],
    ],
    'Educational Use' => [
    'getEducationalUse',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-educational-uses',
        ],
    ],
    ],
    'Educational Level' => [
    'getEducationalLevels',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-educational-levels',
        ],
    ],
    ],
    'Educational Subject' => [
    'getEducationalSubjects',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-educational-subjects',
        ],
    ],
    ],
    'Learning Resource Type' => [
    'getEducationalMaterialType',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-educational-material-type',
        ],
    ],
    ],
    'Objective and Content' => [
    'getEducationalAim',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-educational-aim',
        ],
    ],
    ],
    'Accessibility Feature' => [
    'getAccessibilityFeatures',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-accessibility-features',
        ],
    ],
    ],
    'Accessibility Hazard' => [
    'getAccessibilityHazards',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-accessibility-hazard',
        ],
    ],
    ],
    'Publication Frequency' => [
    'getPublicationFrequency',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extendedFrequency',
        ],
    ],
    ],
    'Playing Time' => [
    'getPlayingTimes',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extendedPlayTime',
        ],
    ],
    ],
    'Color' => [
    'getColor',
    'data-color.phtml',
    [
        'context' => [
            'class' => 'record-color',
        ],
    ],
    ],
    'Sound' => [
    'getSound',
    'data-sound.phtml',
    [
        'context' => [
            'class' => 'record-sound',
        ],
    ],
    ],
    'Aspect Ratio' => [
    'getAspectRatio',
    'data-escapeHtml',
    [
        'context' => [
            'class' => 'record-aspect-ratio',
        ],
    ],
    ],
    'Hardware' => [
    'getHardwareRequirements',
    'data-hardwareRequirements.phtml',
    [
        'context' => [
            'class' => 'record-hardware',
        ],
    ],
    ],
    'System Format' => [
    'getSystemDetails',
    'data-systemFormat.phtml',
    [
        'context' => [
            'class' => 'extendedSystem',
        ],
    ],
    ],
    'Audience' => [
    'getTargetAudienceNotes',
    'data-escapeHtml',
    [
        'context' => [
            'class' => 'extendedAudience',
        ],
    ],
    ],
    'Awards' => [
    'getAwards',
    'data-forwardFields.phtml',
    [
        'context' => [
            'class' => 'extendedAwards',
        ],
    ],
    ],
    'Production Credits' => [
    'getProductionCredits',
    'data-escapeHtml',
    [
        'context' => [
            'class' => 'extendedCredits',
        ],
    ],
    ],
    'Bibliography' => [
    'getBibliographyNotes',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'extendedBibliography',
        ],
    ],
    ],
    'ISBN' => [
    'getISBNs',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extendedISBNs',
        ],
    ],
    ],
    'ISSN' => [
    'getISSNs',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extendedISSNs',
        ],
    ],
    ],
    'DOI' => [
    'getCleanDOI',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extended-doi',
        ],
    ],
    ],
    'Related Items' => [
    'getRelationshipNotes',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extendedRelatedItems',
        ],
    ],
    ],
    'Access Restrictions' => [
    'getAccessRestrictions',
    'data-accrest.phtml',
    [
        'context' => [
            'class' => 'extendedAccess',
        ],
    ],
    ],
    'Access' => [
    'getAccessRestrictions',
    'data-accrest.phtml',
    [
        'context' => [
            'class' => 'extendedAccess',
        ],
    ],
    ],
    'Terms of Use' => [
    'getTermsOfUse',
    'data-termsOfUse.phtml',
    [
        'context' => [
            'class' => 'extendedTermsOfUse',
        ],
    ],
    ],
    'Security Classification' => [
    'getSecurityClassification',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'security-classification',
        ],
    ],
    ],
    'Finding Aid' => [
    'getFindingAids',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'extendedFindingAids',
        ],
    ],
    ],
    'Finding Aid Extended' => [
    'getFindingAidsExtended',
    'data-findingAids.phtml',
    [
        'context' => [
            'class' => 'extendedFindingAids',
            'title' => 'FindingAid',
        ],
    ],
    ],
    'Publication_Place' => [
    'getHierarchicalPlaceNames',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'publicationPlace',
        ],
    ],
    ],
    'Author Notes' => [
    true,
    'data-authorNotes.phtml',
    [
        'context' => [
            'class' => 'extendedAuthorNotes',
        ],
    ],
    ],
    'Location' => [
    'getPhysicalLocations',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordPhysicalLocation',
        ],
    ],
    ],
    'Location LIDO' => [
    'getPhysicalLocationsExtended',
    'data-locations.phtml',
    [
        'context' => [
            'class' => 'recordPhysicalLocation',
            'title' => 'Location',
        ],
    ],
    ],
    'Date' => [
    'getUnitDate',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordDaterange',
        ],
    ],
    ],
    'Dates' => [
    'getUnitDates',
    'data-lines-with-detail.phtml',
    [
        'context' => [
            'title' => 'Date',
        ],
    ],
    ],
    'Material Condition' => [
    'getMaterialCondition',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'materialCondition',
        ],
    ],
    ],
    'Contained In' => [
    'getAllRecordLinks',
    'data-containedIn.phtml',
    [
        'context' => [
            'class' => 'isPartOf',
        ],
    ],
    ],
    'Source of Acquisition' => [
    'getAcquisitionSource',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordAcquisition',
        ],
    ],
    ],
    'Medium of Performance' => [
    'getMusicCompositions',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-composition',
        ],
    ],
    ],
    'Notated Music Format' => [
    'getNotatedMusicFormat',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordNoteFormat',
        ],
    ],
    ],
    'Event Notice' => [
    'getEventNotice',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordEventNotice',
        ],
    ],
    ],
    'Capture Information' => [
    'getCaptureInformation',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-capture-information',
        ],
    ],
    ],
    'First Lyrics' => [
    'getFirstLyrics',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordFirstLyrics',
        ],
    ],
    ],
    'Trade Availability Note' => [
    'getTradeAvailabilityNote',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordTradeNote',
        ],
    ],
    ],
    'Inspection Details' => [
    'getInspectionDetails',
    'data-inspection.phtml',
    [
        'context' => [
            'class' => 'recordInspection',
        ],
    ],
    ],
    'Scale' => [
    'getMapScale',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-map-scale',
        ],
    ],
    ],
    'Available Online' => [
    'getWebResources',
    'data-detailed-urls.phtml',
    [
        'context' => [
            'class' => 'record-available-online',
            'truncateUrl' => true,
        ],
    ],
    ],
    'Notes' => [
    'getNotes',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-notes',
        ],
    ],
    ],
    'Original Version Notes' => [
    'getOriginalVersionNotes',
    'data-originalVersionNotes.phtml',
    [
        'context' => [
            'class' => 'record-original-version-notes',
        ],
    ],
    ],
    'Place of Origin' => [
    'getAssociatedPlace',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-associated-place',
        ],
    ],
    ],
    'Related Places' => [
    'getRelatedPlacesExtended',
    'data-lines-with-detail.phtml',
    [
        'context' => [
            'class' => 'record-related-place',
        ],
    ],
    ],
    'Time Period of Creation' => [
    'getTimePeriodOfCreation',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-time-period-creation',
        ],
    ],
    ],
    'Uniform Title' => [
    'getCollectiveUniformTitle',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-collective-uniform-title',
        ],
    ],
    ],
    'Standard Codes' => [
    'getStandardCodes',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-standard-codes',
        ],
    ],
    ],
    'Standard Report Number' => [
    'getStandardReportNumbers',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-standard-report-number',
        ],
    ],
    ],
    'Study Program Information Notes' => [
    'getStudyProgramNotes',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-study-program-notes',
        ],
    ],
    ],
    'Publisher or Distributor Number' => [
    'getPubDistNumber',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-pubdist-number',
        ],
    ],
    ],
    'Time Period' => [
    'getTimePeriod',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-time-period',
        ],
    ],
    ],
    'Copyright Notes' => [
    'getCopyrightNotes',
    'data-transEsc.phtml',
    [
        'context' => [
            'class' => 'record-copyright-notes',
        ],
    ],
    ],
    'Language Notes' => [
    'getLanguageNotes',
    'data-languageNotes.phtml',
    [
        'context' => [
            'class' => 'record-language-notes',
        ],
    ],
    ],
    'Uncontrolled Title' => [
    'getUncontrolledTitle',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'record-uncontrolled-title',
        ],
    ],
    ],
    'archive_authors' => [
    'getAuthorsWithoutRoleHeadings',
    'data-authors.phtml',
    [
        'context' => [
            'title' => '',
            'class' => 'recordAuthors',
        ],
    ],
    ],
    'archive_other_authors' => [
    'getOtherAuthors',
    'data-authors.phtml',
    [
        'context' => [
            'class' => 'recordAuthors',
        ],
    ],
    ],
    'Appraisal' => [
    'getAppraisal',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordAppraisal',
        ],
    ],
    ],
    'Container Information' => [
    'getContainerInformation',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordContainerInformation',
        ],
    ],
    ],
    'Material Arrangement' => [
    'getMaterialArrangement',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'recordMaterialArrangement',
        ],
    ],
    ],
    'Other Related Material' => [
    'getOtherRelatedMaterial',
    'data-otherRelatedMaterial.phtml',
    [
        'context' => [
            'class' => 'other-related-material',
        ],
    ],
    ],
    'Audience Characteristics' => [
    'getAudienceCharacteristics',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'audience-characteristics',
        ],
    ],
    ],
    'Creator Characteristics' => [
    'getCreatorCharacteristics',
    'data-escapeHtml.phtml',
    [
        'context' => [
            'class' => 'creator-characteristics',
        ],
    ],
    ],
    'Citations' => [
    'getCitations',
    'data-citations.phtml',
    [
        'context' => [
            'class' => 'record-citations',
        ],
    ],
    ],
    'Related Events' => [
    'getRelatedEventsExtended',
    'data-allSubjectHeadingsExtended.phtml',
    [
        'context' => [
            'class' => 'recordRelatedEvents',
        ],
    ],
    ],
    'Provenance' => [
    'getProvenance',
    'data-provenance.phtml',
    [
        'context' => [
            'class' => 'recordProvenance',
        ],
    ],
    ],
    'Additional Information AIPA' => [
    'getAdditionalInformation',
    'data-additionalInformation.phtml',
    [
        'context' => [
            'class' => 'recordAdditionalInformation',
            'title' => 'AdditionalInformation',
        ],
    ],
    ],
    ];

    /**
     * Record fields with multiple template lines
     *
     * @var array
     */
    public array $multiTemplateLines = [
        'Events' => [
          'getEvents',
          [DefaultRecord::class, 'getEvents'],
        ],
        'Access Restrictions Extended' => [
          'getExtendedAccessRestrictions',
          [DefaultRecord::class, 'getAccessRestrictions'],
        ],
        'Archive Relations' => [
          'getAuthorsWithRoleHeadings',
          [DefaultRecord::class, 'getRelations'],
        ],
        'Music Compositions Extended' => [
          'getExtendedMusicCompositions',
          [DefaultRecord::class, 'getExtendedMusicCompositions'],
        ],
    ];

    /**
     * Label help function for archive field
     *
     * @param array          $data   Field data
     * @param AbstractRecord $driver Record driver
     *
     * @return string
     */
    public static function archiveLabel($data, $driver)
    {
        return $driver->tryMethod('getArchiveType') === 'collection' ? 'Parent Collection' : 'Parent Archive';
    }

    /**
     * Label help function for other links field
     *
     * @param array $data Field data
     *
     * @return string
     */
    public static function otherLinksLabel($data)
    {
        $label = isset($data[0]) ? $data[0]['heading'] : '';
        return $label ?: 'Other Related Material';
    }

    /**
     * Label help function for secondary authors field
     *
     * @return string
     */
    public static function secondaryAuthorsLabel()
    {
        return 'Contributors';
    }

    /**
     * Multiline constructor function for extended music compositions
     *
     * @param array $data    Field data
     * @param array $options Field options
     *
     * @return array
     */
    public static function getExtendedMusicCompositions($data, $options)
    {

        $final = [];
        $pos = $options['pos'];
        foreach ($data as $field) {
            $label = $field['partial'] ? 'Partial Medium of Performance' : 'Medium of Performance';
            $final[] = [
              'label' => $label,
              'values' => $field['items'],
              'options' => [
                'pos' => $pos++,
                'renderType' => 'RecordDriverTemplate',
                'template' => 'data-music-composition.phtml',
                'context' => [
                  'class' => 'record-composition',
                ],
              ],
            ];
        }
        return $final;
    }

    /**
     * Multiline constructor function for archive relations
     *
     * @param array $data    Field data
     * @param array $options Field options
     *
     * @return array
     */
    public static function getRelations($data, $options)
    {
        $relationsByRole = [];
        foreach ($data as $relation) {
            $role = ($relation['role'] ?? '0') ?: '0';
            if (!isset($relationsByRole[$role])) {
                $relationsByRole[$role] = [];
            }
            unset($relation['role']);
            $relationsByRole[$role][] = $relation;
        }
        $final = [];
        $pos = $options['pos'];
        foreach ($relationsByRole as $role => $relations) {
            $final[] = [
              'label' => $role !== '0' ? "CreatorRoles::$role" : null,
              'values' => $relations,'options' => [
                'pos' => $pos++,
                'renderType' => 'RecordDriverTemplate',
                'template' => 'data-authors.phtml',
                'context' => [
                  'class' => 'recordRelations',
                  'schemaLabel' => null,
                ],
              ],
            ];
        }
        return $final;
    }

    /**
     * Multiline constructor function for access restrictions
     *
     * @param array $data    Field data
     * @param array $options Field options
     *
     * @return array
     */
    public static function getAccessRestrictions($data, $options)
    {
        $final = [];
        $pos = $options['pos'];
        $useSubHeadings = is_array(array_values($data)[0]);
        foreach ($data as $type => $values) {
            $values = $useSubHeadings && $values ? array_values($values) : $values;
            $label = $useSubHeadings ? "access_restrictions_$type" : null;
            if (
                $useSubHeadings && isset($options['hideSubheadings'])
                && in_array($label, $options['hideSubheadings'])
            ) {
                $label = null;
            }
            $final[] = [
            'label' => $label,
            'values' => $values,
            'options' => [
              'pos' => $pos++,
              'renderType' => 'RecordDriverTemplate',
              'template' => 'data-escapeHtml.phtml',
              'context' => [
                  'class' => 'extendedAccess',
                  'type' => "access_restrictions_$type",
                  'schemaLabel' => null,
                ],
              ],
            ];
        }
        return $final;
    }

    /**
     * Multiline constructor function for lido event types field
     *
     * @param array $data    Field data
     * @param array $options Field options
     *
     * @return array
     */
    public static function getEvents($data, $options)
    {
        $final = [];
        $pos = $options['pos'];
        foreach ($data as $eventType => $events) {
            $final[] = [
            'values' => $events,
            'options' => [
                'pos' => $pos++,
                'renderType' => 'RecordDriverTemplate',
                'template' => 'data-mainFormat.phtml',
                'context' => [
                'class' => 'recordEvents',
                ],
                'labelFunction' => function ($data, $driver) use ($eventType) {
                    if (!$eventType) {
                        return'';
                    }
                    $mainFormat = $driver->getMainFormat();
                    $keys = [
                    "lido_event_type_{$mainFormat}_$eventType",
                    "lido_event_type_$eventType",
                    ];
                    foreach ($keys as $key) {
                        $label = $this->translator->translate($key);
                        if ($key !== $label) {
                            return$key;
                        }
                    }
                    return'';
                },
              ],
            ];
        }
        return$final;
    }
}
