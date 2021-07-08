<?php
/**
 * Functions for reading XML records.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2018-2019.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Finna\RecordDriver;

/**
 * Functions for reading XML records.
 *
 * Assumption: raw XML data can be found in $this->fields['fullrecord'].
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait XmlReaderTrait
{
    /**
     * XML record. Access only via getXMLRecord() as this is initialized lazily.
     *
     * @var \SimpleXMLElement
     */
    protected $lazyXmlRecord = null;

    /**
     * Get access to the raw SimpleXMLElement object.
     *
     * @return \SimpleXMLElement
     */
    public function getXmlRecord()
    {
        if (null === $this->lazyXmlRecord) {
            $this->lazyXmlRecord
                = simplexml_load_string($this->fields['fullrecord']);
            if (false === $this->lazyXmlRecord) {
                throw new \Exception('Cannot Process XML Record');
            }
        }
        return $this->lazyXmlRecord;
    }

    /**
     * Given a path will try to find all the nodes and return them in an array
     * 
     * @param string            $path Nodes to search
     * @param \SimpleXMLElement $xml  Xml node object
     * 
     * @return array
     */
    public function getXmlNodes(string $path, \SimpleXMLElement $xml = null): array
    {
        $exploded = explode('/', $path);
        $result = [];
        if (count($exploded) > 0 && empty($exploded[0])) {
            array_shift($exploded);
        }
        $formatted = [];

        // Format paths into a more readable format
        foreach ($exploded as $path) {
            if (!empty($path)) {
                $filters = ['not' => [], 'is' => []];
                $start = strpos($path, '@[');
                $node = '';
                // Check for any filters
                if ($start !== false) {
                    [$node, $attrs] = explode('@[', $path, 2);
                    if (!empty($attrs)) {
                        // Remove the last character as it is ] 
                        $extracted = mb_substr($attrs, 0, -1);
                        $values = explode(',', $extracted);
                        for ($i = 0; $i < count($values); $i++) {
                            $cur = $values[$i];
                            if (strpos($cur, '==') !== false) {
                                [$key, $value] = explode('==', $cur, 2);
                                $filters['is'][$key] = $value;
                            } else if (strpos($cur, '!=') !== false) {
                                [$key, $value] = explode('!=', $cur, 2);
                                $filters['not'][$key] = $value;
                            }
                        }
                    }
                } else {
                    $node = $path;
                }
                $formatted[] = compact('node', 'filters');
            }
        }
        if (count($formatted) > 0) {
            $result = $this->parseNodes($xml ?? $this->getXmlRecord(), $formatted);
        }

        return $result;
    }

    /**
     * Function to check the nodes if occurence is found
     * Filters to fetch only certain nodes can be marked like follows:
     * /nodetolook@[key0==value0,key1!=value1]
     *
     * @param \SimpleXMLElement $xml  Xml node object
     * @param array             $paths Nodes to search
     * 
     * @return array
     */
    protected function parseNodes(\SimpleXMLElement $xml, array $paths): array
    {
        $find = array_shift($paths);
        $node = $find['node'];
        $filters = $find['filters'];
        $found = [];
        if ($nodes = $xml->$node) {
            for ($n = 0; $n < count($nodes); $n++) {
                $allow = true;
                $attrs = $nodes[$n]->attributes();
                foreach ($filters['is'] ?? [] as $key => $value) {
                    if (empty($attrs->$key) || $attrs->$key !== $value) {
                        $allow = false;
                        continue;
                    }
                }
                foreach ($filters['not'] ?? [] as $key => $value) {
                    if (!empty($attrs->$key) && $attrs->$key === $value) {
                        $allow = false;
                        continue;
                    }
                }
                if ($allow) {
                    $found[] = $nodes[$n];
                }
            }
            if (empty($paths) || empty($found)) {
                return $found;
            } else {
                $returned = [];
                for ($i = 0; $i < count($found); $i++) {
                    if ($result = $this->parseNodes($found[$i], $paths)) {
                        $returned = array_merge($result, $returned);
                    }
                }
                $found = $returned;
            }
        }
        return $found;
    }
}
