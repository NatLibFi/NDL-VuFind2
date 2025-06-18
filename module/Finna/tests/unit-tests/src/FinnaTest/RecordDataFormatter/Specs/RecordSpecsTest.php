<?php

/**
 * RecordDataFormatter Test Class
 *
 * PHP version 7
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
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\RecordDataFormatter\Specs;

use Finna\RecordDataFormatter\Specs\AipaRecord;
use Finna\RecordDataFormatter\Specs\CollectionRecord;
use Finna\RecordDataFormatter\Specs\DefaultRecord;
use Finna\RecordDataFormatter\Specs\Ead3Record;
use Finna\RecordDataFormatter\Specs\EadRecord;
use Finna\RecordDataFormatter\Specs\ForwardRecord;
use Finna\RecordDataFormatter\Specs\LidoRecord;
use Finna\RecordDataFormatter\Specs\LrmiRecord;
use Finna\RecordDataFormatter\Specs\MarcRecord;
use Finna\RecordDataFormatter\Specs\PrimoRecord;
use Finna\RecordDataFormatter\Specs\QdcRecord;

/**
 * RecordDataFormatter Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class RecordSpecsTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\ConfigPluginManagerTrait;
    use \VuFindTest\Feature\FixtureTrait;
    use \VuFindTest\Feature\ViewTrait;
    use \VuFindTest\Feature\PathResolverTrait;

    /**
     * Default record field keys in order to be displayed
     *
     * @var array
     */
    protected array $finnaDefaultRecordFields = [
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
     * Collection record field keys in order to be displayed
     *
     * @var array
     */
    protected array $finnaCollectionRecordFields = [
        'Record Links',
        'child_records',
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
     * Primo record field keys in order to be displayed
     *
     * @var array
     */
    protected array $primoRecordFields = [
        'New Title',
        'Previous Title',
        'Description FWD',
        'Physical Description',
        'Language',
        'Item Notes',
        'Edition',
        'Series',
        'Subjects',
        'Additional Information',
        'child_records',
        'Record Links',
        'Source Collection',
        'Publish date',
        'Publication Frequency',
        'Playing Time',
        'System Format',
        'Audience',
        'Awards',
        'Production Credits',
        'Bibliography',
        'ISBN',
        'ISSN',
        'DOI',
        'Related Items',
        'Access',
        'Finding Aid',
        'Publication_Place',
        'Author Notes',
        'Citations',
    ];

    /**
     * Aipa record field keys in order to be displayed
     *
     * @var array
     */
    protected array $aipaRecordFields = [
        'Subject Place',
        'Subject Date',
        'subjects_extended',
        'Related Events',
        'Provenance',
        'Additional Information AIPA',
    ];

    /**
     * Ead3 record field keys in order to be displayed
     *
     * @var array
     */
    protected array $ead3RecordFields = [
        'Genre',
        'New Title',
        'Previous Title',
        'Presenters',
        'Other Titles',
        'Format',
        'Archive Origination',
        'Archive',
        'Archive Series',
        'Archive File',
        'Extent',
        'Language',
        'original_work_language',
        'Item Notes',
        'Content Description',
        'Item History',
        'Unit IDs',
        'Publisher',
        'Edition',
        'Subject Actor',
        'subjects_extended',
        'Additional Information Extended',
        'Related Materials',
        'Publish date',
        'Keywords',
        'Education Programs',
        'Publication Frequency',
        'Playing Time',
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
        'Finding Aid Extended',
        'Publication_Place',
        'Author Notes',
        'Location',
        'Dates',
        'Material Condition',
        'Access Restrictions Extended',
        'Related Places',
        'archive_authors',
        'archive_other_authors',
        'Archive Relations',
        'Appraisal',
        'Container Information',
        'Material Arrangement',
        'Other Related Material',
    ];

    /**
     * Ead record field keys in order to be displayed
     *
     * @var array
     */
    protected array $eadRecordFields = [
        'Genre',
        'New Title',
        'Previous Title',
        'Presenters',
        'Other Titles',
        'Format',
        'Archive Origination',
        'Archive',
        'Archive Series',
        'Archive File',
        'Extent',
        'Language',
        'original_work_language',
        'Item Notes',
        'Unit ID',
        'Authors',
        'Publisher',
        'Edition',
        'Subjects',
        'Additional Information',
        'Record Links',
        'Publish date',
        'Keywords',
        'Education Programs',
        'Publication Frequency',
        'Playing Time',
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
        'Finding Aid',
        'Publication_Place',
        'Author Notes',
        'Location',
        'Date',
        'Related Places',
    ];

    /**
     * Forward record field keys in order to be displayed
     *
     * @var array
     */
    protected array $forwardRecordFields = [
        'Genre',
        'Age Limit',
        'Original Work',
        'New Title',
        'Previous Title',
        'Secondary Authors',
        'Actors',
        'Item Description FWD',
        'Description FWD',
        'Press Reviews',
        'Music',
        'Physical Description',
        'Language',
        'original_work_language',
        'Inventory ID',
        'Published',
        'Series',
        'Subjects',
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
        'child_records',
        'Record Links',
        'Online Access',
        'Publish date',
        'Keywords',
        'Education Programs',
        'Publication Frequency',
        'Playing Time',
        'Color',
        'Sound',
        'Aspect Ratio',
        'System Format',
        'Audience',
        'Awards',
        'Production Credits',
        'Bibliography',
        'ISBN',
        'ISSN',
        'DOI',
        'Related Items',
        'Access',
        'Finding Aid',
        'Publication_Place',
        'Author Notes',
        'Inspection Details',
        'Related Places',
    ];

    /**
     * Lido record field keys in order to be displayed
     *
     * @var array
     */
    protected array $lidoRecordFields = [
        'Published in',
        'Format',
        'Parent Archive',
        'Parent Collection',
        'Parent Series',
        'Parent Work',
        'Parent Unclassified Entity',
        'Extent',
        'Language',
        'original_work_language',
        'Organisation',
        'Collection',
        'Inventory ID',
        'Other ID',
        'Measurements',
        'Inscriptions',
        'Other Classification',
        'Events',
        'Edition',
        'lido_editions',
        'Subject Detail',
        'Subject Place',
        'Subject Date',
        'Subject Actor',
        'SubjectsWithoutPlaces',
        'Publications',
        'Other Classifications',
        'Introduction',
        'child_records',
        'ISBN',
        'ISSN',
        'DOI',
        'Author Notes',
        'Location LIDO',
        'Available Online',
    ];

    /**
     * Lrmi record field keys in order to be displayed
     *
     * @var array
     */
    protected array $lrmiRecordFields = [
        'Genre',
        'Published in',
        'New Title',
        'Previous Title',
        'Actors',
        'Item Description FWD',
        'Identifiers',
        'Presenters',
        'Other Titles',
        'Format',
        'Physical Medium',
        'Physical Description',
        'Extent',
        'Language',
        'original_work_language',
        'Item Notes',
        'Organisation',
        'Inventory ID',
        'Authors',
        'Published',
        'Edition',
        'Series',
        'Subjects',
        'Additional Information',
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
        'Finding Aid',
        'Publication_Place',
        'Author Notes',
        'Contained In',
        'Related Places',
    ];

    /**
     * Marc record field keys in order to be displayed
     *
     * @var array
     */
    protected array $marcRecordFields = [
        'Genre',
        'Age Limit',
        'New Title',
        'Previous Title',
        'Projected Publication Date',
        'Dissertation Note',
        'Other Links',
        'Presenters Marc',
        'Other Titles',
        'Physical Description',
        'Language',
        'original_work_language',
        'Language of Abstract',
        'Item Notes',
        'Local Note',
        'Inventory ID',
        'Publisher',
        'Series',
        'Country of Producing Entity',
        'Classification',
        'Dewey Classification',
        'subjects_extended',
        'Methodology',
        'Manufacturer',
        'Production',
        'Additional Information',
        'child_records',
        'Record Links',
        'Publish date',
        'Keywords',
        'Education Programs',
        'Accessibility Feature',
        'Accessibility Hazard',
        'Publication Frequency',
        'Playing Time',
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
        'Access',
        'Terms of Use',
        'Security Classification',
        'Finding Aid',
        'Publication_Place',
        'Author Notes',
        'Source of Acquisition',
        'Music Compositions Extended',
        'Notated Music Format',
        'Event Notice',
        'Capture Information',
        'First Lyrics',
        'Trade Availability Note',
        'Scale',
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
        'Audience Characteristics',
        'Creator Characteristics',
    ];

    /**
     * Qdc record field keys in order to be displayed
     *
     * @var array
     */
    protected array $qdcRecordFields = [
        'Genre',
        'Published in',
        'New Title',
        'Previous Title',
        'Presenters',
        'Physical Medium',
        'Physical Description',
        'Language',
        'original_work_language',
        'Item Notes',
        'Inventory ID',
        'Edition',
        'Series',
        'Subjects',
        'Additional Information',
        'child_records',
        'Keywords',
        'Education Programs',
        'Publication Frequency',
        'Playing Time',
        'System Format',
        'Audience',
        'Awards',
        'Production Credits',
        'Bibliography',
        'ISBN',
        'ISSN',
        'DOI',
        'Related Items',
        'Access',
        'Finding Aid',
        'Publication_Place',
        'Author Notes',
        'Contained In',
        'Related Places',
    ];

    /**
     * Test default record core specs
     *
     * @return void
     */
    public function testDefaultSpecs(): void
    {
        $defaultRecordSpec = $this->getMockBuilder(DefaultRecord::class)
            ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $defaultRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->finnaDefaultRecordFields, array_keys($result));
    }

    /**
     * Test collection record core specs
     *
     * @return void
     */
    public function testCollectionRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(CollectionRecord::class)
            ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->finnaCollectionRecordFields, array_keys($result));
    }

    /**
     * Test primo record core specs
     *
     * @return void
     */
    public function testPrimoRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(PrimoRecord::class)
            ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->primoRecordFields, array_keys($result));
    }

    /**
     * Test aipa record core specs
     *
     * @return void
     */
    public function testAipaRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(AipaRecord::class)
            ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->aipaRecordFields, array_keys($result));
    }

    /**
     * Test ead record core specs
     *
     * @return void
     */
    public function testEadRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(EadRecord::class)
            ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->eadRecordFields, array_keys($result));
    }

    /**
     * Test lrmi record core specs
     *
     * @return void
     */
    public function testLrmiRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(LrmiRecord::class)
        ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->lrmiRecordFields, array_keys($result));
    }

    /**
     * Test qdc record core specs
     *
     * @return void
     */
    public function testQdcRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(QdcRecord::class)
        ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->qdcRecordFields, array_keys($result));
    }

    /**
     * Test lido record core specs
     *
     * @return void
     */
    public function testLidoRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(LidoRecord::class)
        ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->lidoRecordFields, array_keys($result));
    }

    /**
     * Test forward record core specs
     *
     * @return void
     */
    public function testForwardRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(ForwardRecord::class)
        ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->forwardRecordFields, array_keys($result));
    }

    /**
     * Test ead3 record core specs
     *
     * @return void
     */
    public function testEad3RecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(Ead3Record::class)
        ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->ead3RecordFields, array_keys($result));
    }

    /**
     * Test marc record core specs
     *
     * @return void
     */
    public function testMarcRecordSpecs(): void
    {
        $collectionRecordSpec = $this->getMockBuilder(MarcRecord::class)
        ->onlyMethods([])->setConstructorArgs([[]])->getMock();
        $result = $collectionRecordSpec->getDefaultCoreSpecs();
        $this->assertEquals($this->marcRecordFields, array_keys($result));
    }
}
