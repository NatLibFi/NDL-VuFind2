<?php
/**
 * Model for Forward authority records in Solr.
 *
 * PHP version 7
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
class SolrAuthForward extends SolrAuthDefault
{
    use XmlReaderTrait;

    /**
     * Get an array of alternative titles for the record.
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        $doc = $this->getMainElement();

        $names = [];
        foreach ($doc->CAgentName as $name) {
            if ((string)$name->AgentNameType === '00') {
                $attr = $name->AgentNameType->attributes();
                $name = (string)$name->PersonName;
                if (isset($attr->{'henkilo-muu_nimi-tyyppi'})) {
                    $type = (string)$attr->{'henkilo-muu_nimi-tyyppi'};
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
        return explode(
            PHP_EOL,
            $this->getBiographicalNote('henkilo-biografia-tyyppi', 'biografia')
        );
    }

    /**
     * Return corporation establishment date date and place.
     *
     * @return string
     */
    public function getEstablishedDate()
    {
        if ($this->isPerson()) {
            return '';
        }
        if ($date = $this->getAgentDate('birth')) {
            return $this->formatDateAndPlace($date);
        }
        return '';
    }

    /**
     * Return corporation termination date date and place.
     *
     * @return string
     */
    public function getTerminatedDate()
    {
        if ($this->isPerson()) {
            return '';
        }
        if ($date = $this->getAgentDate('death')) {
            return $this->formatDateAndPlace($date);
        }
        return '';
    }

    /**
     * Format death/birth date and place.
     *
     * @param array $date Array with keys 'date' and possibly 'place'
     *
     * @return string
     */
    protected function formatDateAndPlace($date)
    {
        $result = $date['date'];
        if ($place = ($date['place'] ?? null)) {
            $result .= " ($place)";
        }
        return $result;
    }

    /**
     * Return awards.
     *
     * @return string[]
     */
    public function getAwards()
    {
        return explode(
            PHP_EOL,
            $this->getBiographicalNote('henkilo-biografia-tyyppi', 'palkinnot')
        );
    }

    /**
     * Return an array of image URLs associated with this record with keys:
     * - url         Image URL
     * - description Description text
     * - rights      Rights
     *   - copyright   Copyright (e.g. 'CC BY 4.0') (optional)
     *   - description Human readable description (array)
     *   - link        Link to copyright info
     *
     * @param string $language Language for copyright information
     *
     * @return array
     */
    public function getAllImages($language = 'fi', $includePdf = false)
    {
        $images = [];

        foreach ($this->getXmlRecord()->children() as $xml) {
            foreach ($xml->ProductionEvent as $event) {
                $attributes = $event->ProductionEventType->attributes();
                if (empty($attributes{'elokuva-elonet-materiaali-kuva-url'})) {
                    continue;
                }
                $url = (string)$attributes{'elokuva-elonet-materiaali-kuva-url'};
                if (!empty($xml->Title->PartDesignation->Value)) {
                    $partAttrs = $xml->Title->PartDesignation->Value->attributes();
                    $desc = (string)$partAttrs{'kuva-kuvateksti'};
                } else {
                    $desc = '';
                }
                $rights = [];
                if (!empty($attributes{'finna-kayttooikeus'})) {
                    $rights['copyright'] = (string)$attributes{'finna-kayttooikeus'};
                    $link = $this->getRightsLink(
                                                 strtoupper($rights['copyright']), $language
                                                 );
                    if ($link) {
                        $rights['link'] = $link;
                    }
                }
                $images[] = [
                             'urls' => [
                                        'small' => $url,
                                        'medium' => $url,
                                        'large' => $url
                                        ],
                             'description' => $desc,
                             'rights' => $rights
                             ];
            }
        }
        return $images;
    }

    /**
     * Returns an array of parameter to send to Finna's cover generator.
     * Falls back to VuFind's getThumbnail if no record image with the
     * given index was found.
     *
     * @param string $size  Size of thumbnail
     * @param int    $index Image index
     *
     * @return array|bool
     */
    public function getRecordImage($size = 'small', $index = 0)
    {
        if ($images = $this->getAllImages()) {
            if (isset($images[$index]['urls'][$size])) {
                $params = $images[$index]['urls'][$size];
                if (!is_array($params)) {
                    $params = [
                        'url' => $params
                    ];
                }
                if ($size == 'large') {
                    $params['fullres'] = 1;
                }
                $params['id'] = $this->getUniqueId();
                return $params;
            }
        }
        return false;
    }

    /**
     * Return biographical note.
     *
     * @param string $type    Note type
     * @param string $typeVal Note type value
     *
     * @return string
     */
    protected function getBiographicalNote($type, $typeVal)
    {
        $doc = $this->getMainElement();
        if (isset($doc->BiographicalNote)) {
            foreach ($doc->BiographicalNote as $bio) {
                $txt = (string)$bio;
                $attr = $bio->attributes();
                if (isset($attr->{$type})
                    && (string)$attr->{$type} === $typeVal
                ) {
                    return $this->sanitizeHTML((string)$bio);
                }
            }
        }
        return null;
    }

    /**
     * Get the main metadata element
     *
     * @return SimpleXMLElement
     */
    protected function getMainElement()
    {
        $nodes = (array)$this->getXmlRecord()->children();
        $node = reset($nodes);
        return is_array($node) ? reset($node) : $node;
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
        $doc = $this->getMainElement();
        if (isset($doc->AgentDate)) {
            foreach ($doc->AgentDate as $d) {
                if (isset($d->AgentDateEventType)) {
                    $dateType = (int)$d->AgentDateEventType;
                    $date = (string)$d->DateText;
                    $place =  (string)$d->LocationName;
                    if (($type === 'birth' && $dateType === 51)
                        || ($type == 'death' && $dateType === 52)
                    ) {
                        return ['date' => $date, 'place' => $place];
                    }
                }
            }
        }

        return null;
    }
}
