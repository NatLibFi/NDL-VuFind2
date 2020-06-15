<?php
/**
 * Model for R2 records.
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
 * Model for R2 records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class R2Ead3 extends SolrEad3
{
    use XmlReaderTrait {
        getXmlRecord as _getXmlRecord;
    }

    /**
     * Used for identifying search backends
     *
     * @var string
     */
    protected $sourceIdentifier = 'R2';

    /**
     * Does this record contain restricted metadata?
     *
     * @return bool
     */
    public function hasRestrictedMetadata()
    {
        $xml = $this->getXmlRecord();
        return isset($xml->accessrestrict);
    }

    /**
     * Is restricted metadata included with the record, i.e. is the user
     * authorized to access restricted metadata?
     *
     * @return bool
     */
    public function isRestrictedMetadataIncluded()
    {
        return ($this->fields['display_restriction_id_str'] ?? false) === '10';
    }

    /**
     * Get the Hierarchy Type (false if none)
     *
     * @return string|bool
     */
    public function getHierarchyType()
    {
        return 'R2';
    }

    /**
     * Is social media sharing allowed
     *
     * @return boolean
     */
    public function socialMediaSharingAllowed()
    {
        return false;
    }

    /**
     * Allow record to be emailed?
     *
     * @return boolean
     */
    public function emailRecordAllowed()
    {
        return false;
    }

    /**
     * Indicate whether export is disabled for a particular format.
     *
     * @param string $format Export format
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function exportDisabled($format)
    {
        return true;
    }

    /**
     * Get access to the raw SimpleXMLElement object.
     *
     * @return \SimpleXMLElement
     */
    public function getXmlRecord()
    {
        try {
            return $this->_getXmlRecord();
        } catch (\Exception $e) {
            return new \SimpleXmlElement('<xml><did></did></xml>');
        }
    }
}
