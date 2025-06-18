<?php

/**
 * Lido RecordDataFormatter specs.
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
 * Lido RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class LidoRecord extends \Finna\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Order of record fields in record page
     *
     * @var array
     */
    protected array $recordFieldOrder = [
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
}
