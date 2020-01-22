<?php
/**
 * Model for Marc authority records in Solr.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace Finna\RecordDriver;

/**
 * Model for Forward authority records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrAuthMarc extends SolrAuthDefault
{
    use \VuFind\RecordDriver\MarcReaderTrait;

    /**
     * Return relations to other authority records.
     *
     * @return array
     */
    public function getRelations()
    {
        $result = [];
        foreach (['500', '510'] as $code) {
            foreach ($this->getMarcRecord()->getFields($code) as $field) {
                $id = $field->getSubfield('0');
                $name = $field->getSubfield('a');
                $type = $field->getSubfield('i');
                if (empty($type)) {
                    $type = $field->getSubfield('b');
                }
                if (!$name || !$id) {
                    continue;
                }
                $id = $this->getDataSource() . '.' . $id->getData();
                if ($type) {
                    $type = trim($type->getData());
                    if (':' === substr($type, -1)) {
                        $type = substr($type, 0, -1);
                    }
                }
                $result[] = [
                    'id' => $id,
                    'name' => $name->getData(),
                    'type' => $type
                ];
            }
        }
        return $result;
    }

    /**
     * Return additional information.
     *
     * @return string
     */
    public function getAdditionalInformation()
    {
        foreach ($this->getMarcRecord()->getFields('680') as $field) {
            if ($res = $field->getSubfield('i')) {
                return $res->getData();
            }
        }
        return '';
    }

    /**
     * Return place of residence.
     *
     * @return string
     */
    public function getPlaceOfResidence()
    {
        foreach ($this->getMarcRecord()->getFields('370') as $field) {
            if ($res = $field->getSubfield('e')) {
                return $res->getData();
            }
        }
        return '';
    }

    /**
     * Return birth date and place.
     *
     * @param boolean $force Return established date for corporations?
     *
     * @return string
     */
    public function getBirthDate($force = false)
    {
        $date = $this->fields['birth_date'] ?? '';
        $place = $this->fields['birth_place'] ?? '';

        if (empty($place)) {
            return $date;
        }
        return "$date ({$place})";
    }

    /**
     * Return death date and place.
     *
     * @param boolean $force Return terminated date for corporations?
     *
     * @return string
     */
    public function getDeathDate($force = false)
    {
        $date = $this->fields['death_date'] ?? '';
        $place = $this->fields['death_place'] ?? '';

        if (empty($place)) {
            return $date;
        }
        return "$date ({$place})";
    }

    /**
     * Return description
     *
     * @return string|null
     */
    public function getSummary()
    {
        $result = [];
        foreach ($this->getMarcRecord()->getFields('678') as $field) {
            if ($subfield = $field->getSubfield('a')) {
                $result[] = $subfield->getData();
            }
        }
        
        return $result;
    }

    /**
     * Return authority data sources.
     *
     * @return array|null
     */
    public function getSources()
    {
        $result = [];
        foreach ($this->getMarcRecord()->getFields('670') as $field) {
            $source = $field->getSubfield('a');

            $url = $field->getSubfield('u');
            if (!$source ) {
                continue;
            }
            $source = $source->getData();
            $info = $field->getSubfield('b');
            $result[] = [
                'title' => $source,
                'info' => $info ? $info->getData() : null,
                'url' => $url ? $url->getData() : null
            ];

        }
        return $result;
    }

    /**
     * Get an array of alternative names for the record.
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        $result = [];
        foreach (['400', '410'] as $fieldCode) {
            foreach ($this->getMarcRecord()->getFields($fieldCode) as $field) {
                if ($subfield = $field->getSubfield('a')) {
                    $name = $subfield->getData();
                    if ($date = $field->getSubfield('d')) {
                        $name .= ' (' . $date->getData() . ')';
                    }
                    $result[] = $name;
                }
            }
        }
        
        return $result;
    }

    /**
     * Return associated place.
     *
     * @return string
     */
    public function getAssociatedPlace()
    {
        if ($field = $this->getMarcRecord()->getField('370')) {
            if ($subfield = $field->getSubfield('f')) {
                return $subfield->getData();
            }
        }
        return '';
    }

}
