<?php

/**
 * Qdc RecordDataFormatter specs.
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
 * Qdc RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class QdcRecord extends \Finna\RecordDataFormatter\Specs\DefaultRecord
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
}
