<?php
/**
 * Functions for reading MARC records.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2020.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Finna\RecordDriver;

/**
 * Functions for reading MARC records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait MarcReaderTrait
{
    /**
     * Get selected subfields from a MARC field
     *
     * @param \File_MARC_Data_Field $field     Field
     * @param array                 $subfields Subfields
     * @param bool                  $concat    Concat subfields into a string?
     * If false, the subfields are returned as an associative array.
     *
     * @return string|array
     */
    protected function getFieldSubfields(
        \File_MARC_Data_Field $field, $subfields, $concat = true
    ) {
        $result = [];
        foreach ($field->getSubfields() as $code => $content) {
            if (in_array($code, $subfields)) {
                $data = $content->getData();
                if ($concat) {
                    $result[] = $data;
                } else {
                    $result[$code] = $data;
                }
            }
        }
        if ($concat) {
            $result = implode(' ', $result);
        }
        return $result;
    }
}
