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
     * Return an array of image URLs associated with this record with keys:
     * - url         Image URL
     * - description Description text
     * - rights      Rights
     *   - copyright   Copyright (e.g. 'CC BY 4.0') (optional)
     *   - description Human readable description (array)
     *   - link        Link to copyright info
     *
     * @param string $language   Language for copyright information
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return mixed
     */
    public function getAllImages($language = 'fi', $includePdf = false)
    {
        // LRMI records encapsulated in AIPA records do not have PDF materials
        return parent::getAllImages($language, false);
    }

    /**
     * Return array of materials with keys:
     * - id: record id
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
            $notes = (string)$material->comment;
            $position = (int)$material->position ?? 0;
            $materials[] = compact(
                'id',
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
