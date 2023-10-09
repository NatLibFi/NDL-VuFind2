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

use Laminas\View\Helper\AbstractHelper;
use NatLibFi\FinnaCodeSets\FinnaCodeSets;
use NatLibFi\FinnaCodeSets\Model\EducationalLevel\EducationalLevelInterface;
use NatLibFi\FinnaCodeSets\Utility\EducationalData;
use VuFind\RecordDriver\AbstractBase;

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
     * @var AbstractBase
     */
    protected AbstractBase $driver;

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
     * @param AbstractBase $driver Record driver object.
     *
     * @return Aipa
     */
    public function __invoke($driver): Aipa
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Render educational levels and subjects.
     *
     * @param array $educationalData Educational data from record driver
     *
     * @return string
     */
    public function renderLevelsAndSubjects(array $educationalData): string
    {
        $translate = $this->getView()->plugin('translate');
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

            if (!empty($items)) {
                $html .= $component('@@molecules/lists/finna-tag-list', [
                    'title' => $translate('Aipa::' . $levelCodeValue) . ':',
                    'items' => $items,
                ]);
            }
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
        $translate = $this->getView()->plugin('translate');
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

            $componentData = [];

            // Learning areas.
            if (!empty($levelData[EducationalData::LEARNING_AREAS])) {
                $componentData[EducationalData::LEARNING_AREAS]
                    = EducationalData::getPrefLabels(
                        $levelData[EducationalData::LEARNING_AREAS],
                        $langcode
                    );
                $componentData[EducationalData::LEARNING_AREAS . 'Title']
                    = $translate('Aipa::' . EducationalData::LEARNING_AREAS);
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
                            = $translate('Aipa::' . $contentsOrObjectivesKey);
                    }
                }
            }

            // Transversal competences.
            if (!empty($levelData[EducationalData::TRANSVERSAL_COMPETENCES])) {
                $componentData[EducationalData::TRANSVERSAL_COMPETENCES]
                    = EducationalData::getPrefLabels(
                        $levelData[EducationalData::TRANSVERSAL_COMPETENCES],
                        $langcode
                    );
                $componentData[EducationalData::TRANSVERSAL_COMPETENCES . 'Title']
                    = $translate('Aipa::' . EducationalData::TRANSVERSAL_COMPETENCES);
            }

            // Vocational common units.
            if (!empty($levelData[EducationalData::VOCATIONAL_COMMON_UNITS])) {
                $componentData[EducationalData::VOCATIONAL_COMMON_UNITS]
                    = EducationalData::getPrefLabels(
                        $levelData[EducationalData::VOCATIONAL_COMMON_UNITS],
                        $langcode
                    );
                $componentData[EducationalData::VOCATIONAL_COMMON_UNITS . 'Title']
                    = $translate('Aipa::' . EducationalData::VOCATIONAL_COMMON_UNITS);
            }

            if (!empty($componentData)) {
                $levelHtml = $component('@@organisms/data/finna-educational-level-data', $componentData);
                $html .= $component('@@molecules/containers/finna-truncate', [
                    'content' => $levelHtml,
                    'label' => $translate('Aipa::' . $levelCodeValue),
                    'topToggle' => -1,
                ]);
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
