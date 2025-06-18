<?php

/**
 * Lrmi RecordDataFormatter specs.
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
 * Lrmi RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class LrmiRecord extends \Finna\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Order of record fields in record page
     *
     * @var array
     */
    protected array $recordFieldOrder = [
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
}
