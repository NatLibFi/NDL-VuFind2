<?php
/**
 * Model for AIPA records in Solr.
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
 * Model for AIPA records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrAipa extends SolrQdc
{
    /**
     * Record driver plugin manager.
     *
     * @var PluginManager
     */
    protected PluginManager $driverManager;

    /**
     * Attach record driver plugin manager.
     *
     * @param PluginManager $driverManager Record driver plugin manager
     *
     * @return void
     */
    public function attachDriverManager(PluginManager $driverManager): void
    {
        $this->driverManager = $driverManager;
    }

    /**
     * Return all items encapsulated in the AIPA record.
     *
     * @return array
     */
    public function getItems(): array
    {
        $xml = $this->getXmlRecord();
        $items = [];
        foreach ($xml->item as $item) {
            $format = ucfirst(strtolower((string)$item->format));
            $method = "get{$format}Driver";
            if (is_callable([$this, $method])) {
                $items[] = $this->$method($item);
            }
        }

        return $items;
    }

    /**
     * Return record driver instance for an encapsulated LRMI record.
     *
     * @param \SimpleXMLElement $item AIPA item XML
     *
     * @return AipaLrmi
     */
    protected function getLrmiDriver(\SimpleXMLElement $item): AipaLrmi
    {
        $driver = $this->driverManager->get('AipaLrmi');
        $driver->setRawData(
            [
                'title' => (string)$item->title,
                'fullrecord' => $item->asXML(),
            ]
        );

        return $driver;
    }
}
