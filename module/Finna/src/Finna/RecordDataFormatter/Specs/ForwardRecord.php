<?php

/**
 * Forward RecordDataFormatter specs.
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
 * Forward RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class ForwardRecord extends \Finna\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Order of record fields in record page
     *
     * @var array
     */
    protected array $recordFieldOrder = [
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
}
