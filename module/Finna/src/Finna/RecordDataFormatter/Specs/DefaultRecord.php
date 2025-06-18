<?php

/**
 * DefaultRecord RecordDataFormatter specs.
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2025.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Thomas Wagener <wagener@hebis.uni-frankfurt.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */

namespace Finna\RecordDataFormatter\Specs;

use Finna\RecordDataFormatter\Specs\Utils\RecordFieldsTrait;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;

/**
 * DefaultRecord RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Thomas Wagener <wagener@hebis.uni-frankfurt.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class DefaultRecord extends \VuFind\RecordDataFormatter\Specs\DefaultRecord
{
    use RecordFieldsTrait;

    /**
     * Order of record fields in record page
     *
     * @var array
     */
    protected array $recordFieldOrder = [
        'Genre',
        'Age Limit',
        'Original Work',
        'Published in',
        'New Title',
        'Previous Title',
        'Secondary Authors',
        'Actors',
        'Item Description FWD',
        'Description FWD',
        'Identifiers',
        'Press Reviews',
        'Music',
        'Projected Publication Date',
        'Dissertation Note',
        'Other Links',
        'Presenters',
        'Presenters Marc',
        'Other Titles',
        'Format',
        'Parent Archive',
        'Parent Collection',
        'Parent Subcollection',
        'Parent Series',
        'Parent Work',
        'Parent Unclassified Entity',
        'Archive Origination',
        'Archive',
        'Archive Series',
        'Archive File',
        'Physical Medium',
        'Physical Description',
        'Extent',
        'Language',
        'original_work_language',
        'Language of Abstract',
        'Item Notes',
        'Local Note',
        'Organisation',
        'Collection',
        'Content Description',
        'Item History',
        'Inventory ID',
        'Other ID',
        'Measurements',
        'Inscriptions',
        'Other Classification',
        'Events',
        'Unit ID',
        'Unit IDs',
        'Authors',
        'Publisher',
        'Published',
        'Edition',
        'Series',
        'Country of Producing Entity',
        'Classification',
        'Dewey Classification',
        'lido_editions',
        'Subject Detail',
        'Subject Place',
        'Subject Date',
        'Subject Actor',
        'Subjects',
        'SubjectsWithoutPlaces',
        'subjects_extended',
        'Methodology',
        'Publications',
        'Other Classifications',
        'Introduction',
        'Manufacturer',
        'Production',
        'Production Costs',
        'Funding',
        'Distribution',
        'Premiere Night',
        'Premiere Theaters',
        'Broadcasting Dates',
        'Number of Viewers',
        'Film Festivals',
        'Foreign Distribution',
        'Film Copies',
        'Other Screenings',
        'Movie Thanks',
        'Exterior Images',
        'Interior Images',
        'Studios',
        'Filming Location Notes',
        'Filming Date',
        'Archive Films',
        'Additional Information',
        'Additional Information Extended',
        'child_records',
        'Record Links',
        'Related Materials',
        'Online Access',
        'Source Collection',
        'Publish date',
        'Keywords',
        'Education Programs',
        'Educational Role',
        'Educational Use',
        'Educational Level',
        'Educational Subject',
        'Learning Resource Type',
        'Objective and Content',
        'Accessibility Feature',
        'Accessibility Hazard',
        'Publication Frequency',
        'Playing Time',
        'Color',
        'Sound',
        'Aspect Ratio',
        'Hardware',
        'System Format',
        'Audience',
        'Awards',
        'Production Credits',
        'Bibliography',
        'ISBN',
        'ISSN',
        'DOI',
        'Related Items',
        'Access Restrictions',
        'Access',
        'Terms of Use',
        'Security Classification',
        'Finding Aid',
        'Finding Aid Extended',
        'Publication_Place',
        'Author Notes',
        'Location',
        'Location LIDO',
        'Date',
        'Dates',
        'Material Condition',
        'Contained In',
        'Access Restrictions Extended',
        'Source of Acquisition',
        'Medium of Performance',
        'Music Compositions Extended',
        'Notated Music Format',
        'Event Notice',
        'Capture Information',
        'First Lyrics',
        'Trade Availability Note',
        'Inspection Details',
        'Scale',
        'Available Online',
        'Notes',
        'Original Version Notes',
        'Place of Origin',
        'Related Places',
        'Time Period of Creation',
        'Uniform Title',
        'Standard Codes',
        'Standard Report Number',
        'Study Program Information Notes',
        'Publisher or Distributor Number',
        'Time Period',
        'Copyright Notes',
        'Language Notes',
        'Uncontrolled Title',
        'archive_authors',
        'archive_other_authors',
        'Archive Relations',
        'Appraisal',
        'Container Information',
        'Material Arrangement',
        'Other Related Material',
        'Audience Characteristics',
        'Creator Characteristics',
        'Citations',
        'Related Events',
        'Provenance',
        'Additional Information AIPA',
    ];

    /**
     * Initialize specs.
     *
     * @return void
     */
    protected function init(): void
    {
        parent::init();

        $this->setDefaults(
            'authority',
            [$this, 'getDefaultAuthoritySpecs']
        );
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs(): array
    {
        $spec = new SpecBuilder();

        foreach ($this->getDefaultCoreFields() as $key => $data) {
            if ($data[0] === true) {
                // Multi-line
                [, $dataMethod, $callback, $options] = $data;
                $spec->setMultiLine($key, $dataMethod, $callback, $options);
            } else {
                [, $dataMethod, $template, $options] = $data;
                $spec->setTemplateLine($key, $dataMethod, $template, $options);
            }
        }
        return $spec->getArray();
    }

    /**
     * Utility function for getting fields in core metadata
     *
     * @return array
     */
    protected function getDefaultCoreFields()
    {
        $pos = 10;
        $lines = [];
        $setTemplateLine
            = function (
                $key,
                $data
            ) use (
                &$lines,
                &$pos
            ) {
                $pos += 100;
                $dataMethod = $data[0];
                $template = $data[1];
                $options = $data[2] ?? [];
                $options['pos'] = $pos;
                $lines[$key] = [false, $dataMethod, $template, $options];
            };

        $setMultiTemplateLine
            = function (
                $key,
                $data
            ) use (
                &$lines,
                &$pos
            ) {
                $pos += 100;
                $dataMethod = $data[0];
                $callback = $data[1];
                $options = $data[2] ?? [];
                $options['pos'] = $pos;
                $lines[$key] = [true, $dataMethod, $callback, $options];
            };
        foreach ($this->recordFieldOrder as $key) {
            if ($template = $this->singleTemplateLines[$key] ?? false) {
                $setTemplateLine($key, $template);
                continue;
            }
            if ($template = $this->multiTemplateLines[$key] ?? false) {
                $setMultiTemplateLine($key, $template);
            }
        }
        return $lines;
    }

    /**
     * Get default specifications for displaying data in the description tab.
     *
     * @return array
     */
    public function getDefaultDescriptionSpecs(): array
    {
        $spec = new SpecBuilder();
        $spec->setLine('Summary', 'getSummary');
        $spec->setLine('Abstract', 'getAbstractNotes');
        $spec->setLine('Review', 'getReviewNotes');
        $spec->setLine('Content Advice', 'getContentAdviceNotes');
        $spec->setLine('Published', 'getDateSpan');
        $spec->setLine('Item Notes', 'getGeneralNotes');
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setLine('Format', 'getSystemDetails');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine('ISBN', 'getISBNs');
        $spec->setLine('ISSN', 'getISSNs');
        $spec->setLine('DOI', 'getCleanDOI');
        $spec->setLine('Related Items', 'getRelationshipNotes');
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in the description tab.
     *
     * @return array
     */
    public function getDefaultAuthoritySpecs()
    {
        $spec = new SpecBuilder();
        $spec->setLine('Date of birth', 'getBirthDate');
        $spec->setLine('Place of birth', 'getBirthPlace');
        $spec->setLine('Date of death', 'getDeathDate');
        $spec->setLine('Place of death', 'getDeathPlace');

        $spec->setLine('Established', 'getEstablishedDate');
        $spec->setLine('Terminated', 'getTerminatedDate');
        $spec->setLine('Awards', 'getAwards');

        $spec->setLine('Occupation', 'getOccupations');
        $spec->setLine('Field of Activity', 'getFieldsOfActivity');
        $spec->setTemplateLine(
            'Other Forms of Name',
            'getAlternativeTitles',
            'data-lines-with-detail.phtml'
        );
        $spec->setLine('Associated Place', 'getAssociatedPlace');
        $spec->setTemplateLine(
            'Related Places',
            'getRelatedPlaces',
            'data-lines-with-detail.phtml'
        );
        $spec->setTemplateLine(
            'Identifiers',
            'getOtherIdentifiers',
            'data-lines-with-detail.phtml'
        );
        $spec->setLine('Historical Information', 'getHistory');
        $spec->setTemplateLine(
            'Publications',
            'getRelatedPublications',
            'data-relatedPublications.phtml'
        );
        $spec->setTemplateLine('Sources', 'getSources', 'data-sources.phtml');
        $spec->setTemplateLine(
            'Related Authorities',
            'getRelations',
            'data-relations-author.phtml'
        );
        $spec->setTemplateLine(
            'Associated Groups',
            'getAssociatedGroups',
            'data-lines-with-detail.phtml'
        );
        $spec->setLine('Additional Information', 'getAdditionalInformation');

        return $spec->getArray();
    }
}
