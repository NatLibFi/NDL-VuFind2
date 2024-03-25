<?php

/**
 * OAI Server class
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2019.
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
 * @package  OAI_Server
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\OAI;

/**
 * OAI Server class
 *
 * This class provides OAI server functionality.
 *
 * @category VuFind
 * @package  OAI_Server
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Server extends \VuFind\OAI\Server
{
    /**
     * Initialize data about metadata formats. (This is called on demand and is
     * defined as a separate method to allow easy override by child classes).
     *
     * @return void
     */
    protected function initializeMetadataFormats()
    {
        parent::initializeMetadataFormats();

        $this->metadataFormats['oai_ead'] = [
            'schema' => 'https://www.loc.gov/ead/ead.xsd',
            'namespace' => 'http://www.loc.gov/ead/'];
        $this->metadataFormats['oai_forward'] = [
            'schema' => 'http://forward.cineca.it/schema/EN15907-forward-v1.0.xsd',
            'namespace' => 'http://project9forward.eu/schemas/EN15907-forward'];
        $this->metadataFormats['oai_lido'] = [
            'schema' => 'http://www.lido-schema.org/schema/v1.0/lido-v1.0.xsd',
            'namespace' => 'http://www.lido-schema.org/'];

        $qdc = 'http://dublincore.org/schemas/xmls/qdc/2008/02/11/qualifieddc.xsd';
        $this->metadataFormats['oai_qdc'] = [
            'schema' => $qdc,
            'namespace' => 'urn:dc:qdc:container'];
    }

    /**
     * Validate the from and until parameters for the listRecords method.
     *
     * @param int $from  String for start date.
     * @param int $until String for end date.
     *
     * @return bool      True if invalid, false if not.
     */
    protected function isBadDate($from, $until)
    {
        $dt = \DateTime::createFromFormat('Y-m-d', substr($until, 0, 10));
        if (!$this->dateTimeCreationSuccessful($dt)) {
            return true;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', substr($from, 0, 10));
        if (!$this->dateTimeCreationSuccessful($dt)) {
            return true;
        }
        // Check for different date granularity
        if (strpos($from, 'T') && strpos($from, 'Z')) {
            if (strpos($until, 'T') && strpos($until, 'Z')) {
                // This is good
            } else {
                return true;
            }
        } elseif (strpos($until, 'T') && strpos($until, 'Z')) {
            return true;
        }

        $from_time = $this->normalizeDate($from);
        $until_time = $this->normalizeDate($until, '23:59:59');
        if ($from_time > $until_time) {
            throw new \Exception('noRecordsMatch:from vs. until');
        }
        if ($from_time < $this->normalizeDate($this->earliestDatestamp)) {
            return true;
        }
        return false;
    }

    /**
     * Check if a DateTime was successfully created without errors or warnings
     *
     * @param \DateTime|false $dt DateTime or false (return value of createFromFormat)
     *
     * @return bool
     */
    protected function dateTimeCreationSuccessful(\DateTime|false $dt): bool
    {
        if (false === $dt) {
            return false;
        }
        $errors = $dt->getLastErrors();
        if (false === $errors) {
            return true;
        }
        return empty($errors['errors']) && empty($errors['warnings']);
    }
}
