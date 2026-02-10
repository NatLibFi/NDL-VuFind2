<?php

/**
 * Model for Forward authority records in Solr.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver;

/**
 * Model for Forward authority records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrAuthForward extends SolrAuthDefault implements \Psr\Log\LoggerAwareInterface
{
    use Feature\SolrAuthFinnaTrait;
    use Feature\SolrForwardTrait {
        getBirthPlace as _getBirthPlace;
        getDeathPlace as _getDeathPlace;
    }
    use Feature\FinnaXmlReaderTrait;
    use Feature\FinnaUrlCheckTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Runtime cache for method results to avoid duplicate processing
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Get an array of alternative titles for the record.
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        $xml = $this->getAllRecordsXmlDoc();
        $recordNode = $this->getMainRecordNode($xml);

        $names = [];
        foreach ($xml->all($recordNode, 'CAgentName') as $name) {
            $agentNameType = $xml->first($name, 'AgentNameType');
            if ($agentNameType && $xml->value($agentNameType) === '00') {
                $name = $xml->firstValue($name, 'PersonName');
                if ($type = $xml->attr($agentNameType, 'henkilo-muu_nimi-tyyppi')) {
                    $name .= " ($type)";
                }
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * Return description
     *
     * @return string|null
     */
    public function getSummary()
    {
        $desc = $this->isPerson()
          ? $this->getBiographicalNote('henkilo-biografia-tyyppi', 'biografia')
          : $this->getBiographicalNote();

        if (empty($desc)) {
            return null;
        }
        return explode(PHP_EOL, $desc);
    }

    /**
     * Return birth date.
     *
     * @param bool $force Return established date for corporations?
     *
     * @return string
     */
    public function getBirthDate($force = false)
    {
        if (!$this->isPerson() && !$force) {
            return '';
        }
        return $this->getAgentDate('birth')['date'] ?? '';
    }

    /**
     * Return birth place.
     *
     * @param bool $force Return established date for corporations?
     *
     * @return string
     */
    public function getBirthPlace($force = false)
    {
        if (!$this->isPerson() && !$force) {
            return '';
        }
        // Apparently phpstan doesn't understand 'as' in use clause
        // @phpstan-ignore-next-line
        return $this->_getBirthPlace();
    }

    /**
     * Return death date.
     *
     * @param bool $force Return terminated date for corporations?
     *
     * @return string
     */
    public function getDeathDate($force = false)
    {
        if (!$this->isPerson() && !$force) {
            return '';
        }
        return $this->getAgentDate('death')['date'] ?? '';
    }

    /**
     * Return death place.
     *
     * @param bool $force Return terminated date for corporations?
     *
     * @return string
     */
    public function getDeathPlace($force = false)
    {
        if (!$this->isPerson() && !$force) {
            return '';
        }
        // Apparently phpstan doesn't understand 'as' in use clause
        // @phpstan-ignore-next-line
        return $this->_getDeathPlace();
    }

    /**
     * Return corporation establishment date and place.
     *
     * @return string
     */
    public function getEstablishedDate()
    {
        if ($this->isPerson()) {
            return '';
        }
        return $this->getBirthDate(true);
    }

    /**
     * Return corporation termination date and place.
     *
     * @return string
     */
    public function getTerminatedDate()
    {
        if ($this->isPerson()) {
            return '';
        }
        return $this->getDeathDate(true);
    }

    /**
     * Return awards.
     *
     * @return string[]
     */
    public function getAwards()
    {
        $awards = trim($this->getBiographicalNote('henkilo-biografia-tyyppi', 'palkinnot'));
        return $awards ? array_map('trim', explode(PHP_EOL, $awards)) : [];
    }

    /**
     * Allow record image to be downloaded?
     *
     * @param array $image Image to check
     *
     * @return bool
     */
    public function allowRecordImageDownload(array $image = []): bool
    {
        return false;
    }

    /**
     * Return biographical note.
     *
     * @param ?string $type    Note type
     * @param ?string $typeVal Note type value
     *
     * @return string
     */
    protected function getBiographicalNote(?string $type = null, ?string $typeVal = null)
    {
        $xml = $this->getAllRecordsXmlDoc();
        $recordNode = $this->getMainRecordNode($xml);
        foreach ($xml->all($recordNode, 'BiographicalNote') as $bio) {
            if (!$type || ($xml->attr($bio, $type) === $typeVal)) {
                return $xml->value($bio);
            }
        }
        return '';
    }

    /**
     * Return agent event date.
     *
     * @param string $type Date event type
     *
     * @return string
     */
    protected function getAgentDate($type)
    {
        $xml = $this->getAllRecordsXmlDoc();
        $recordNode = $this->getMainRecordNode($xml);
        foreach ($xml->all($recordNode, 'AgentDate') as $d) {
            if ($agentDateEventType = $xml->first($d, 'AgentDateEventType')) {
                $dateType = (int)$xml->value($agentDateEventType);
                $date = $xml->firstValue($d, 'DateText');
                $place = $xml->firstValue($d, 'LocationName');
                if (
                    ($type === 'birth' && $dateType === 51)
                    || ($type == 'death' && $dateType === 52)
                ) {
                    return ['date' => $date, 'place' => $place];
                }
            }
        }

        return null;
    }
}
