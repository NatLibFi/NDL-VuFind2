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
                // Check if the path has any special searches to take in account
                $special = mb_substr($node, 0, 2);
                // >> means all the matching nodes in current nodes
                $specials = ['>>'];
                $flags = [];
                if (in_array($special, $specials)) {
                    switch ($special) {
                    case '>>':
                        $node = mb_substr($node, 2);
                        $flags = ['allChildren' => true, 'name' => $node];
                        break;
                    }
                }
                $formatted[] = compact('node', 'filters', 'flags');
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
     * @param \SimpleXMLElement $xml   Xml node object
     * @param array             $paths Nodes to search
     * 
     * @return array
     */
    protected function parseNodes(\SimpleXMLElement $xml, array $paths, array $flags = []): array
    {
        $searchUntil = false;
        $nodeFlags = [];
        // Check the first node of array
        $find = $paths[0] ?? [];

        if (!empty($flags['allChildren'])) {
            $searchUntil = true;
        }

        // Lets see if node contains any flags
        if (!$searchUntil && !empty($find['flags'])) {
            $nodeFlags = $find['flags'];
            if (!empty($nodeFlags['allChildren'])) {
                $searchUntil = true;
            }
        }

        if (!$searchUntil) {
            array_shift($paths);
            $filters = $find['filters'];
        } else {
            $nodeFlags = $flags;
        }

        $node = $find['node'];

        $found = [];
        $stash = [];
        $nodes = $searchUntil ? $xml->children() : $xml->$node;

        if (!empty($nodes)) {
            for ($n = 0; $n < count($nodes); $n++) {
                $cur = $nodes[$n];
                $allow = true;
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
                if ($allow) {
                    if (empty($paths) || ($searchUntil && $cur->getName() === $node)) {
                        $found[] = $cur;
                    } else {
                        if ($result = $this->parseNodes($cur, $paths, $nodeFlags)) {
                            $found = array_merge($found, $result);
                        }
                    }
                }
            }
        }
        return $found;
    }
}
