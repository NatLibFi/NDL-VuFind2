<?php
/**
 * Helper for Authority recommendations.
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
 * @package  Search
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Search\Solr;

/**
 * Helper for Authority recommendations.
 *
 * @category VuFind
 * @package  Search
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class AuthorityHelper
{
    /**
     * Index field for author2-ids.
     *
     * @var string
     */
    const AUTHOR2_ID_FACET = 'author2_id_str_mv';

    /**
     * Index field for author2-ids.
     *
     * @var string
     */
    const AUTHOR_CORPORATE_ID_FACET = 'author_corporate_id_str_mv';

    /**
     * Index field for author id-role combinations
     *
     * @var string
     */
    const AUTHOR_ID_ROLE_FACET = 'author2_id_role_str_mv';

    /**
     * Index field for author2-ids.
     *
     * @var string
     */
    const TOPIC_ID_FACET = 'topic_id_str_mv';

    /**
     * Delimiter used to separate author id and role.
     *
     * @var string
     */
    const AUTHOR_ID_ROLE_SEPARATOR = '###';

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Search runner
     *
     * @var \VuFind\Search\SearchRunner
     */
    protected $searchRunner;

    /**
     * Translator
     *
     * @var \VuFind\Translator
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param \VuFind\Record\Loader              $recordLoader Record loader
     * @param \VuFind\View\Helper\Root\Translate $translator   Translator view helper
     */
    public function __construct(
        \VuFind\Record\Loader $recordLoader,
        \VuFind\Search\SearchRunner $searchRunner,
        \VuFind\View\Helper\Root\Translate $translator
    ) {
        $this->recordLoader = $recordLoader;
        $this->searchRunner = $searchRunner;
        $this->translator = $translator;
    }

    /**
     * Format displayTexts of a facet set.
     *
     * @param array $facetSet Facet set
     *
     * @return array
     */
    public function formatFacetSet($facetSet)
    {
        foreach ($this->getAuthorIdFacets() as $field) {
            if (isset($facetSet[$field])) {
                return $this->processFacets($facetSet);
            }
        }
        return $facetSet;
    }

    /**
     * Format displayTexts of a facet list.
     *
     * @param string $field  Facet field
     * @param array  $facets Facets
     *
     * @return array
     */
    public function formatFacetList($field, $facets)
    {
        if (!in_array($field, $this->getAuthorIdFacets())) {
            return $facets;
        }
        $result = $this->processFacets([$field => ['list' => $facets]]);
        return $result[$field]['list'];
    }

    /**
     * Resolve authority types for a list of authorities with unknown types.
     * Authority types are needed to display suitable icons together with the
     * link in the UI.
     *
     * @param array                              $authorities List of authority data
     * with 'id' fields. Entries with 'type' fields are omitted.
     * @param \VuFind\RecordDriver\DefaultRecord $driver      Biblio record driver
     *
     * @return array Authority data with 'type' fields.
     */
    public function resolveAuthorityTypes($authorities, $driver)
    {
        if (!$this->recordLoader) {
            return $authorities;
        }

        $ids = [];
        foreach ($authorities as $author) {
            if (!isset($author['type'])) {
                $ids[] = $driver->getAuthorityId($author['id']);
            }
        }
        if ($ids) {
            $records
                = $this->recordLoader->loadBatchForSource($ids, 'SolrAuth', true);
            foreach ($authorities as &$author) {
                foreach ($records as $rec) {
                    list($source, $recId) = explode('.', $rec->getUniqueID(), 2);
                    if ($author['id'] === $recId) {
                        if ($formats = $rec->getFormats()) {
                            $author['type'] = reset($formats);
                        }
                        continue;
                    }
                }
            }
        }
        return $authorities;
    }

    /**
     * Helper function for processing a facet set.
     *
     * @param array $facetSet Facet set
     *
     * @return array
     */
    protected function processFacets($facetSet)
    {
        foreach ($this->getAuthorIdFacets() as $field) {
            $ids = [];
            $facetList = $facetSet[$field]['list'] ?? [];
            foreach ($facetList as $facet) {
                list($id, $role) = $this->extractRole($facet['displayText']);
                $ids[] = $id;
            }

            $records
                = $this->recordLoader->loadBatchForSource($ids, 'SolrAuth', true);
            foreach ($facetList as &$facet) {
                list($id, $role) = $this->extractRole($facet['displayText']);
                foreach ($records as $record) {
                    if ($record->getUniqueId() === $id) {
                        list($displayText, $role)
                            = $this->formatDisplayText($record, $role);
                        $facet['displayText'] = $displayText;
                        $facet['role'] = $role;
                        continue;
                    }
                }
            }
            $facetSet[$field]['list'] = $facetList;
        }
        return $facetSet;
    }

    /**
     * Return index fields that are used in authority searches.
     *
     * @return array
     */
    public function getAuthorIdFacets()
    {
        return [
            AuthorityHelper::AUTHOR_ID_ROLE_FACET,
            AuthorityHelper::AUTHOR2_ID_FACET,
            AuthorityHelper::AUTHOR_CORPORATE_ID_FACET,
            AuthorityHelper::TOPIC_ID_FACET
        ];
    }

    /**
     * Format facet value (display text).
     *
     * @param string  $value        Facet value
     * @param boolean $extendedInfo Wheter to return an array with
     * 'id', 'displayText' and 'role' fields.
     *
     * @return mixed string|array
     */
    public function formatFacet($value, $extendedInfo = false)
    {
        $id = $value;
        $role = null;
        list($id, $role) = $this->extractRole($value);
        $record = $this->recordLoader->load($id, 'SolrAuth', true);
        list($displayText, $role) = $this->formatDisplayText($record, $role);
        return $extendedInfo
            ? ['id' => $id, 'displayText' => $displayText, 'role' => $role]
            : $displayText;
    }

    /**
     * Parse authority id and role.
     *
     * @param string $value Authority id-role
     *
     * @return array
     */
    public function extractRole($value)
    {
        $id = $value;
        $role = null;
        $separator = self::AUTHOR_ID_ROLE_SEPARATOR;
        if (strpos($value, $separator) !== false) {
            list($id, $role) = explode($separator, $value, 2);
        }
        return [$id, $role];
    }

    /**
     * Return biblio records that are linked to author.
     *
     * @param string $id     Authority id
     * @param array  $fields Solr fields to search by (author, topic)
     *
     * @return \VuFind\Search\Results
     */
    public function getRecordsByAuthor(
        $id, $fields = ['author2_id_str_mv', 'author_corporate_id_str_mv']
    ) {
        $query = $this->getRecordsByAuthorQuery($id, $fields);
        return $this->searchRunner->run(
            ['lookfor' => $query, 'fl' => 'id'],
            'Solr',
            function ($runner, $params, $searchId) {
                $params->setLimit(100);
                $params->setPage(1);
            }
        );
    }

    public function getRecordsByAuthorQuery($id, $fields)
    {
        if (count($fields) === 1) {
            return $fields[0] . ":\"$id\"";
        } else {
            return implode(
                ' OR ', array_map(
                    function ($field) use ($id) {
                        return "(${field}:\"$id\")";
                    },
                    $fields
                )
            );
        }
    }
    
    /**
     * Helper function for formatting author-role display text.
     *
     * @param \Finna\RecordDriver\SolrDefault $record Record driver
     * @param string                          $role   Author role
     *
     * @return string
     */
    protected function formatDisplayText($record, $role = null)
    {
        $displayText = $record->getTitle();
        if ($role) {
            $role = mb_strtolower(
                $this->translator->translate("CreatorRoles::$role")
            );
            $displayText .= " ($role)";
        }
        return [$displayText, $role];
    }
}
