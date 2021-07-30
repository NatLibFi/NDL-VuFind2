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
     * Filters to fetch only certain nodes can be marked like follows:
     * /nodetolook@[key0==value0,key1!=value1]
     * For xpath version of // you can use />> to search for all the nodes
     * This will also work
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
        $e = 0;
        // Format paths into a more readable format
        do {
            $path = $exploded[$e];
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
                        $i = 0;
                        do {
                            $cur = $values[$i];
                            if (strpos($cur, '==') !== false) {
                                [$key, $value] = explode('==', $cur, 2);
                                $filters['is'][$key] = $value;
                            } else if (strpos($cur, '!=') !== false) {
                                [$key, $value] = explode('!=', $cur, 2);
                                $filters['not'][$key] = $value;
                            }
                            $i++;
                        } while($i < count($values));
                    }
                } else {
                    $node = $path;
                }
                // Check if the path has any special searches to take in account
                $special = mb_substr($node, 0, 2);
                // >> means all the matching nodes in current nodes
                $specials = ['>>'];
                $flags = [];
                if (in_array($special, $specials)) {
                    switch ($special) {
                    case '>>':
                        $node = mb_substr($node, 2);
                        $flags = ['allUntil' => true];
                        break;
                    }
                }
                $formatted[] = compact('node', 'filters', 'flags');
            }
            $e++;
        } while ($e < count($exploded));
        if (count($formatted) > 0) {
            $result = $this->parseNodes($xml ?? $this->getXmlRecord(), $formatted);
        }

        return $result;
    }

    /**
     * Function to check the nodes if occurence is found
     *
     * @param \SimpleXMLElement $xml   Xml node object
     * @param array             $paths Nodes to search
     * 
     * @return array
     */
    protected function parseNodes(
        \SimpleXMLElement $xml, array $paths
    ): array {
        // Check the first node of array
        $j = 0; // Level of nodes
        $find = $paths[$j++] ?? [];
        if (empty($find)) {
            return [];
        }
        $found = [];
        $lookfor = $find['node'];
        $stash = $xml->$lookfor;
        $nodes = [];
        $all = false;
        $i = 0;
        $prevJ = 0; // Memory of the latest level, used for comparison
        // Loop variables
        $find = [];
        $filters = [];
        $flags = [];
        do {
            $tmp = $stash[$i] ?? [];
            if (empty($tmp)) {
                break;
            }
            if ($prevJ !== $j && !$all) {
                $find = $paths[$j] ?? [];
                $filters = $find['filters'];
                $lookfor = $find['node'];
                $flags = $find['flags'];
            }

            $nodes = [];
            $i++;
            /*if (!empty($find['flags']['allUntil'])) {
                $all = true;
            }*/

            $children = $all ? $tmp->children() : $tmp->$lookfor;
            echo $lookfor;
            // Check the children if they are a match
            $n = 0;

            do {
                $cur = $children[$n] ?? [];
                if (empty($cur)) {
                    break;
                }
                $allow = true;
                if (!empty($filters['is']) || !empty($filters['not'])) {
                    $attrs = $cur->attributes();
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
                }
                if ($allow) {
                    if ($cur->getName() === $lookfor) {
                        $found[] = $cur;
                    } else {
                        $nodes[] = $cur;
                    }
                    
                }
                $n++;
            } while ($n < count($children));

            if (!empty($nodes)) {
                $prevJ = $j;
                if (!$all) {
                    $j++;
                }
                if ($i === count($stash) - 1) {
                    $stash = $nodes;
                    $i = 0;
                    $nodes = [];
                }
            }
        } while ($stash);

        return $found;
    }
}
