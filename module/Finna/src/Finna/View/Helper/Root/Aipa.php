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
    /**
     * Finna Code Sets library instance.
     *
     * @var FinnaCodeSets
     */
    protected FinnaCodeSets $codeSets;

    /**
     * Finna Code Sets library educational data utilities.
     *
     * @var EducationalData
     */
    protected EducationalData $dataUtil;

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
        $this->dataUtil = $codeSets->educationalData();
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
        $levelCodeValues = $this->dataUtil->getMappedLevelCodeValues(
            $educationalData[EducationalData::EDUCATIONAL_LEVELS]
        );
        $html = '';
        foreach ($levelCodeValues as $levelCodeValue) {
            $levelData = $this->dataUtil->getEducationalLevelData($levelCodeValue, $educationalData);
            if (empty($levelData)) {
                continue;
            }
            $items = [];
            foreach ($levelData[EducationalData::EDUCATIONAL_SUBJECTS] ?? [] as $subject) {
                $items[] = $subject->getPrefLabel('fi');
            }
            foreach ($levelData[EducationalData::EDUCATIONAL_SYLLABUSES] ?? [] as $syllabus) {
                $items[] = $syllabus->getPrefLabel('fi');
            }
            foreach ($levelData[EducationalData::EDUCATIONAL_MODULES] ?? [] as $module) {
                $items[] = $module->getPrefLabel('fi');
            }
            $html .= $component('@@molecules/lists/finna-tag-list', [
                'title' => $translate('aipa_' . $levelCodeValue) . ':',
                'items' => $items,
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
        $translate = $this->getView()->plugin('translate');
        $transEsc = $this->getView()->plugin('transEsc');
        $component = $this->getView()->plugin('component');
        $levelCodeValues = $this->dataUtil->getMappedLevelCodeValues(
            $educationalData[EducationalData::EDUCATIONAL_LEVELS]
        );
        $html = '';
        foreach ($levelCodeValues as $levelCodeValue) {
            $educationalLevelData = $this->dataUtil->getEducationalLevelData(
                $levelCodeValue,
                $educationalData
            );
            if (empty($educationalLevelData)) {
                continue;
            }
            $componentData = $this->getStudyContentsOrObjectives(
                $educationalLevelData,
                $educationalData,
                []
            );
            if (!empty($educationalLevelData[EducationalData::TRANSVERSAL_COMPETENCES])) {
                $componentData[EducationalData::TRANSVERSAL_COMPETENCES] = [];
                foreach ($educationalLevelData[EducationalData::TRANSVERSAL_COMPETENCES] as $transversalCompetence) {
                    $componentData[EducationalData::TRANSVERSAL_COMPETENCES][]
                        = $transversalCompetence->getPrefLabel('fi');
                }
                $componentData[EducationalData::TRANSVERSAL_COMPETENCES . 'Title']
                    = $transEsc('aipa_' . EducationalData::TRANSVERSAL_COMPETENCES);
            }
            if (!empty($componentData)) {
                $levelHtml = $component('@@organisms/data/finna-educational-level-data', $componentData);
                $html .= $component('@@molecules/containers/finna-truncate', [
                    'content' => $levelHtml,
                    'label' => $translate('aipa_' . $levelCodeValue),
                    'topToggle' => -1,
                ]);
            }
        }
        return $html;
    }

    /**
     * Get study contents and/or study objectives of a specific educational level.
     *
     * @param array $educationalLevelData Educational level specific educational data
     * @param array $educationalData      Educational data from record driver
     * @param array $componentData        Display component data to be updated
     *
     * @return array Updated component data
     */
    protected function getStudyContentsOrObjectives(
        array $educationalLevelData,
        array $educationalData,
        array $componentData
    ): array {
        $transEsc = $this->getView()->plugin('transEsc');
        $keys = [
            EducationalData::STUDY_CONTENTS,
            EducationalData::STUDY_OBJECTIVES,
        ];
        $subjectLevelKeys = [
            EducationalData::EDUCATIONAL_SUBJECTS,
            EducationalData::EDUCATIONAL_SYLLABUSES,
            EducationalData::EDUCATIONAL_MODULES,
        ];
        foreach ($keys as $key) {
            foreach ($subjectLevelKeys as $subjectLevelKey) {
                $items = $this->getLevelStudyContentsOrObjectivesItems(
                    $educationalLevelData,
                    $educationalData,
                    $key,
                    $subjectLevelKey
                );
                if (!empty($items)) {
                    $componentData[$key] = $items;
                    $componentData[$key . 'Title'] = $transEsc('aipa_' . $key);
                }
            }
        }
        return $componentData;
    }

    /**
     * Get study contents and/or study objectives of a specific educational level
     * and educational subject level.
     *
     * @param array  $educationalLevelData Educational level specific educational data
     * @param array  $educationalData      Educational data from record driver
     * @param string $key                  Data array key for study contents or objectives
     * @param string $subjectLevelKey      Data array key for educational subject level
     *
     * @return array
     */
    protected function getLevelStudyContentsOrObjectivesItems(
        array $educationalLevelData,
        array $educationalData,
        string $key,
        string $subjectLevelKey
    ): array {
        $items = [];
        foreach ($educationalLevelData[$subjectLevelKey] ?? [] as $subjectLevel) {
            $subjectLevelItems = [];
            $levelContentsOrObjectives = $this->dataUtil->getStudyContentsOrObjectives(
                $subjectLevel,
                $educationalData[$key]
            );
            foreach ($levelContentsOrObjectives as $contentsOrObjective) {
                $item = $contentsOrObjective->getPrefLabel('fi');
                // Avoid duplicates.
                if (!in_array($item, $subjectLevelItems)) {
                    $subjectLevelItems[] = $item;
                }
            }
            if (!empty($subjectLevelItems)) {
                $items[$subjectLevel->getPrefLabel('fi')] = $subjectLevelItems;
            }
        }
        return $items;
    }
}
