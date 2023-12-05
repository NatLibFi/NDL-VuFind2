<?php

/**
 * AIPA view helper
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  View_Helpers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\View\Helper\Root;

use Finna\RecordDriver\AipaLrmi;
use Finna\RecordDriver\SolrAipa;
use Laminas\View\Helper\AbstractHelper;
use NatLibFi\FinnaCodeSets\FinnaCodeSets;
use NatLibFi\FinnaCodeSets\Model\EducationalLevel\EducationalLevelInterface;
use NatLibFi\FinnaCodeSets\Utility\EducationalData;

use function count;

/**
 * AIPA view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Aipa extends AbstractHelper
{
    protected const EDUCATIONAL_LEVEL_SORT_ORDER = [
        EducationalLevelInterface::PRIMARY_SCHOOL,
        EducationalLevelInterface::LOWER_SECONDARY_SCHOOL,
        EducationalLevelInterface::UPPER_SECONDARY_SCHOOL,
        EducationalLevelInterface::VOCATIONAL_EDUCATION,
        EducationalLevelInterface::HIGHER_EDUCATION,
    ];

    /**
     * Finna Code Sets library instance.
     *
     * @var FinnaCodeSets
     */
    protected FinnaCodeSets $codeSets;

    /**
     * Record driver
     *
     * @var SolrAipa|AipaLrmi
     */
    protected SolrAipa|AipaLrmi $driver;

    /**
     * Constructor
     *
     * @param FinnaCodeSets $codeSets Finna Code Sets library instance
     */
    public function __construct(FinnaCodeSets $codeSets)
    {
        $this->codeSets = $codeSets;
    }

    /**
     * Store a record driver object and return this object.
     *
     * @param SolrAipa|AipaLrmi $driver Record driver object.
     *
     * @return Aipa
     */
    public function __invoke(SolrAipa|AipaLrmi $driver): Aipa
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Render all subject headings.
     *
     * @return string
     */
    public function renderAllSubjectHeadings(): string
    {
        $headings = $this->driver->getAllSubjectHeadings();
        if (empty($headings)) {
            return '';
        }

        $recordHelper = $this->getView()->plugin('record');
        $items = [];
        foreach ($headings as $field) {
            $item = '';
            $subject = '';
            if (count($field) == 1) {
                $field = explode('--', $field[0]);
            }
            $i = 0;
            foreach ($field as $subfield) {
                $item .= ($i++ == 0) ? '' : ' &#8594; ';
                $subject = trim($subject . ' ' . $subfield);
                $item .= $recordHelper($this->driver)->getLinkedFieldElement(
                    'subject',
                    $subfield,
                    ['name' => $subject],
                    ['class' => ['backlink']]
                );
            }
            $items[] = $item;
        }

        $component = $this->getView()->plugin('component');
        return $component('@@molecules/lists/finna-tag-list', [
            'title' => 'Subjects',
            'items' => $items,
            'htmlItems' => true,
        ]);
    }

    /**
     * Render educational levels and educational subjects.
     *
     * @param array $educationalData Educational data from record driver
     *
     * @return string
     */
    public function renderEducationalLevelsAndSubjects(array $educationalData): string
    {
        if (empty($educationalData)) {
            return '';
        }

        $component = $this->getView()->plugin('component');
        $langcode = $this->view->layout()->userLang;

        // Basic education levels are mapped to primary school and lower secondary
        // school levels.
        $levelCodeValues = EducationalData::getMappedLevelCodeValues(
            $educationalData[EducationalData::EDUCATIONAL_LEVELS] ?? []
        );

        usort($levelCodeValues, [$this, 'sortEducationalLevels']);

        $html = '';
        foreach ($levelCodeValues as $levelCodeValue) {
            $levelData = EducationalData::getEducationalLevelData($levelCodeValue, $educationalData);
            if (empty($levelData)) {
                continue;
            }

            $items = [];
            foreach (EducationalData::EDUCATIONAL_SUBJECT_LEVEL_KEYS as $subjectLevelKey) {
                foreach ($levelData[$subjectLevelKey] ?? [] as $subjectLevel) {
                    $items[] = $subjectLevel->getPrefLabel($langcode);
                }
            }
            $html .= $component('@@molecules/lists/finna-tag-list', [
                'title' => 'Aipa::' . $levelCodeValue,
                'items' => $items,
                'translateItems' => false,
            ]);
        }
        return $html;
    }

    /**
     * Render study contents and objectives.
     *
     * @param array $educationalData Educational data from record driver
     *
     * @return string
     */
    public function renderStudyContentsAndObjectives(array $educationalData): string
    {
        if (empty($educationalData)) {
            return '';
        }

        $component = $this->getView()->plugin('component');
        $transEsc = $this->getView()->plugin('transEsc');
        $langcode = $this->view->layout()->userLang;

        // Basic education levels are mapped to primary school and lower secondary
        // school levels.
        $levelCodeValues = EducationalData::getMappedLevelCodeValues(
            $educationalData[EducationalData::EDUCATIONAL_LEVELS] ?? []
        );

        usort($levelCodeValues, [$this, 'sortEducationalLevels']);

        $html = '';
        $levelsHtml = [];
        foreach ($levelCodeValues as $levelCodeValue) {
            $levelData = EducationalData::getEducationalLevelData($levelCodeValue, $educationalData);
            if (empty($levelData)) {
                continue;
            }

            $componentData = [];

            // Learning areas.
            if (!empty($levelData[EducationalData::LEARNING_AREAS])) {
                $componentData['learningAreas']
                    = EducationalData::getPrefLabels(
                        $levelData[EducationalData::LEARNING_AREAS],
                        $langcode
                    );
                $componentData['learningAreasTitle']
                    = 'Aipa::' . EducationalData::LEARNING_AREAS;
            }

            // Educational subjects, study contents and objectives.
            foreach (EducationalData::EDUCATIONAL_SUBJECT_LEVEL_KEYS as $subjectLevelKey) {
                foreach (EducationalData::STUDY_CONTENTS_OR_OBJECTIVES_KEYS as $contentsOrObjectivesKey) {
                    $items = [];
                    foreach ($levelData[$subjectLevelKey] ?? [] as $subjectLevel) {
                        $contentsOrObjectives = EducationalData::getStudyContentsOrObjectives(
                            $subjectLevel,
                            $levelData[$contentsOrObjectivesKey]
                        );
                        $subjectLevelItems
                            = EducationalData::getPrefLabels($contentsOrObjectives, $langcode);
                        if (!empty($subjectLevelItems)) {
                            $items[$subjectLevel->getPrefLabel($langcode)] = $subjectLevelItems;
                        }
                    }
                    if (!empty($items)) {
                        $componentData[$contentsOrObjectivesKey] = $items;
                        $componentData[$contentsOrObjectivesKey . 'Title']
                            = 'Aipa::' . $contentsOrObjectivesKey;
                    }
                }
            }

            // Transversal competences.
            if (!empty($levelData[EducationalData::TRANSVERSAL_COMPETENCES])) {
                $componentData['transversalCompetences']
                    = EducationalData::getPrefLabels(
                        $levelData[EducationalData::TRANSVERSAL_COMPETENCES],
                        $langcode
                    );
                $componentData['transversalCompetencesTitle']
                    = 'Aipa::' . EducationalData::TRANSVERSAL_COMPETENCES;
            }

            // Vocational common units.
            if (!empty($levelData[EducationalData::VOCATIONAL_COMMON_UNITS])) {
                $componentData['vocationalCommonUnits']
                    = EducationalData::getPrefLabels(
                        $levelData[EducationalData::VOCATIONAL_COMMON_UNITS],
                        $langcode
                    );
                $componentData['vocationalCommonUnitsTitle']
                    = 'Aipa::' . EducationalData::VOCATIONAL_COMMON_UNITS;
            }

            if (!empty($componentData)) {
                $componentData['sectionHeadingLevel'] = 5;
                $levelsHtml[$levelCodeValue] = $component(
                    '@@organisms/data/finna-educational-level-data',
                    $componentData
                );
            }
        }
        foreach ($levelsHtml as $levelCodeValue => $levelHtml) {
            if (count($levelsHtml) > 1) {
                $html .= $component('@@molecules/containers/finna-truncate', [
                    'content' => $levelHtml,
                    'element' => 'div',
                    'label' => 'Aipa::' . $levelCodeValue,
                    'topToggle' => -1,
                ]);
            } else {
                $html .= '<h4>' . $transEsc('Aipa::' . $levelCodeValue) . '</h4>';
                $html .= $levelHtml;
            }
        }
        return $html;
    }

    /**
     * Sort educational levels.
     *
     * @param string $a Level A
     * @param string $b Level B
     *
     * @return int
     */
    protected function sortEducationalLevels(string $a, string $b): int
    {
        return array_search($a, self::EDUCATIONAL_LEVEL_SORT_ORDER)
                > array_search($b, self::EDUCATIONAL_LEVEL_SORT_ORDER)
            ? 1 : -1;
    }
}
