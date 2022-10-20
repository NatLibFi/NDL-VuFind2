<?php
/**
 * Model for LRMI records encapsulated in AIPA records.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Finna\RecordDriver;

use Finna\Record\Loader;
use VuFind\Exception\RecordMissing as RecordMissingException;

/**
 * Model for LRMI records encapsulated in AIPA records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class AipaLrmi extends SolrLrmi
{
    /**
     * Record loader.
     *
     * @var Loader
     */
    protected Loader $recordLoader;

    /**
     * Attach record loader.
     *
     * @param Loader $recordLoader Record loader
     *
     * @return void
     */
    public function attachRecordLoader(Loader $recordLoader)
    {
        $this->recordLoader = $recordLoader;
    }

    /**
     * Return array of materials with keys:
     * - id: record id
     * - driver: record driver
     * - notes: record notes
     * - position: order of listing
     *
     * @return array
     */
    public function getMaterials()
    {
        $xml = $this->getXmlRecord();
        $materials = [];
        foreach ($xml->material as $material) {
            $id = (string)$material->identifier;
            try {
                $driver = $this->recordLoader->load($id);
            } catch (RecordMissingException $e) {
                $driver = null;
            }
            $notes = (string)$material->comment;
            $position = (int)$material->position ?? 0;
            $materials[] = compact(
                'id',
                'driver',
                'notes',
                'position',
            );
        }

        usort(
            $materials,
            function ($a, $b) {
                return $a['position'] <=> $b['position'];
            }
        );

        return $materials;
    }
}
