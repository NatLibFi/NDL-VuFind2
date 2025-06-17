<?php

/**
 * Collection RecordDataFormatter specs.
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

/**
 * Collection RecordDataFormatter specs.
 *
 * @category VuFind
 * @package  RecordDataFormatter
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Thomas Wagener <wagener@hebis.uni-frankfurt.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class CollectionRecord extends \Finna\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Collection record specific order for fields. Entries are sorted into top of the list.
     *
     * @var array
     */
    protected array $collectionFieldSortOrder = [
      'Record Links' => [],
      'child_records' => [],
    ];

    /**
     * Initialize specs.
     *
     * @return void
     */
    protected function init(): void
    {
        parent::init();
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs(): array
    {
        $specs = parent::getDefaultCoreSpecs();
        $intersected = array_intersect_key($specs, $this->collectionFieldSortOrder);
        foreach ($intersected as $key => $data) {
          unset($specs[$key]);
        }
        return array_merge($intersected, $specs);
    }
}
