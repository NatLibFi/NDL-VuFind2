<?php

/**
 * Marc RecordDataFormatter specs.
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

namespace Finna\RecordDataFormatter\Specs;

/**
 * Marc RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class MarcRecord extends \Finna\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Order of record fields in record page
     *
     * @var array
     */
    protected array $recordFieldOrder = [
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
}
