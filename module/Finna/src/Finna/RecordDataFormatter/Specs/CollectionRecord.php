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

namespace Finna\RecordDataFormatter\Specs;

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
class CollectionRecord extends \Finna\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Collection record specific order for fields.
     *
     * @var array
     */
    protected array $collectionFieldSortOrder = [
        'Record Links',
        'child_records',
    ];

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs(): array
    {
        $specs = parent::getDefaultCoreSpecs();
        $result = [];
        foreach ($this->collectionFieldSortOrder as $key) {
            if ($value = $specs[$key] ?? false) {
                $result[$key] = $value;
                unset($specs[$key]);
            }
        }
        return array_merge($result, $specs);
    }
}
