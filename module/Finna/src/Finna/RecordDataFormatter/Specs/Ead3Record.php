<?php

/**
 * Ead3 RecordDataFormatter specs.
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
 * Ead3 RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class Ead3Record extends \Finna\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Order of record fields in record page
     *
     * @var array
     */
    protected array $recordFieldOrder = [
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
}
