<?php

/**
 * Model for AIPA LRMI records.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022-2023.
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

use Finna\RecordDriver\Feature\ContainerFormatInterface;
use Finna\RecordDriver\Feature\ContainerFormatTrait;
use NatLibFi\FinnaCodeSets\FinnaCodeSets;
use NatLibFi\FinnaCodeSets\Model\EducationalLevel\EducationalLevelInterface;
use NatLibFi\FinnaCodeSets\Model\EducationalModule\EducationalModuleInterface;
use NatLibFi\FinnaCodeSets\Model\EducationalSubject\EducationalSubjectInterface;
use NatLibFi\FinnaCodeSets\Model\EducationalSyllabus\EducationalSyllabusInterface;
use NatLibFi\FinnaCodeSets\Model\StudyContents\StudyContentsInterface;
use NatLibFi\FinnaCodeSets\Model\StudyObjective\StudyObjectiveInterface;
use NatLibFi\FinnaCodeSets\Utility\EducationalData;

/**
 * Model for AIPA LRMI records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class AipaLrmi extends SolrLrmi implements ContainerFormatInterface
{
    use ContainerFormatTrait;

    /**
     * Finna Code Sets library instance.
     *
     * @var FinnaCodeSets
     */
    protected FinnaCodeSets $codeSets;

    /**
     * Attach Finna Code Sets library instance.
     *
     * @param FinnaCodeSets $codeSets Finna Code Sets library instance
     *
     * @return void
     */
    public function attachCodeSetsLibrary(FinnaCodeSets $codeSets): void
    {
        $this->codeSets = $codeSets;
    }

    /**
     * Get an array of formats/extents for the record
     *
     * @return array
     */
    public function getPhysicalDescriptions(): array
    {
        return [];
    }

    /**
     * Return educational levels
     *
     * @return array
     */
    public function getEducationalLevels()
    {
        $xml = $this->getXmlRecord();
        $levels = [];
        foreach ($xml->learningResource->educationalLevel ?? [] as $level) {
            $levels[] = (string)$level->name;
        }
        return $levels;
    }

    /**
     * Get educational subjects
     *
     * @return array
     */
    public function getEducationalSubjects()
    {
        $xml = $this->getXmlRecord();
        $subjects = [];
        foreach ($xml->learningResource->educationalAlignment ?? [] as $alignment) {
            foreach ($alignment->educationalSubject ?? [] as $subject) {
                $subjects[] = (string)$subject->targetName;
            }
        }
        return $subjects;
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
     * @param string $language   Language for copyright information
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return mixed
     */
    public function getAllImages($language = 'fi', $includePdf = false)
    {
        // AIPA LRMI records do not directly contain PDF files.
        return parent::getAllImages($language, false);
    }

    /**
     * Get educational aim
     *
     * @return array
     */
    public function getEducationalAim()
    {
        $xml = $this->getXmlRecord();
        $contentsAndObjectives = [];
        foreach ($xml->learningResource->teaches ?? [] as $teaches) {
            $contentsAndObjectives[] = (string)$teaches->name;
        }
        return $contentsAndObjectives;
    }

    /**
     * Return study objectives, or null if not found in record.
     *
     * @return ?string
     */
    public function getStudyObjectives(): ?string
    {
        $studyObjectives = null;
        $xml = $this->getXmlRecord();
        foreach ($xml->learningResource as $learningResource) {
            if ($learningResource->studyObjectives) {
                if (null === $studyObjectives) {
                    $studyObjectives = '';
                }
                $studyObjectives .= (string)$learningResource->studyObjectives;
            }
        }
        return $studyObjectives;
    }

    /**
     * Return assignment ideas, or null if not found in record.
     *
     * @return ?string
     */
    public function getAssignmentIdeas(): ?string
    {
        $xml = $this->getXmlRecord();
        if ($xml->assignmentIdeas) {
            return (string)$xml->assignmentIdeas;
        }
        return null;
    }

    /**
     * Get rich educational data, or false if not possible.
     *
     * @return array|false
     */
    public function getEducationalData(): array|false
    {
        $xml = $this->getXmlRecord();
        try {
            $data = [];
            $data[EducationalData::EDUCATIONAL_LEVELS] = [];
            $dataUtil = $this->codeSets->educationalData();
            foreach ($xml->learningResource->educationalLevel ?? [] as $level) {
                $data[EducationalData::EDUCATIONAL_LEVELS][]
                    = $dataUtil->getEducationalLevelByCodeValue($level->termCode);
            }
            $data[EducationalData::EDUCATIONAL_SUBJECTS] = [];
            $data[EducationalData::EDUCATIONAL_SYLLABUSES] = [];
            $data[EducationalData::EDUCATIONAL_MODULES] = [];
            foreach ($xml->learningResource->educationalAlignment ?? [] as $alignment) {
                foreach ($alignment->educationalSubject ?? [] as $xmlSubject) {
                    $subject = $this->codeSets->getEducationalSubjectByUrl($xmlSubject->targetUrl);
                    if ($subject->getId() !== $xmlSubject->identifier) {
                        // XML subject is an educational syllabus or module.
                        $subject = $this->codeSets->getEducationalSubjectById(
                            $xmlSubject->identifier,
                            $subject->getRootEducationalLevelCodeValue()
                        );
                    }
                    if ($subject instanceof EducationalSubjectInterface) {
                        $data[EducationalData::EDUCATIONAL_SUBJECTS][] = $subject;
                    } elseif ($subject instanceof EducationalSyllabusInterface) {
                        $data[EducationalData::EDUCATIONAL_SYLLABUSES][] = $subject;
                    } elseif ($subject instanceof EducationalModuleInterface) {
                        $data[EducationalData::EDUCATIONAL_MODULES][] = $subject;
                    } else {
                        throw new \Exception();
                    }
                }
            }
            $data[EducationalData::STUDY_CONTENTS] = [];
            $data[EducationalData::STUDY_OBJECTIVES] = [];
            foreach ($xml->learningResource->teaches ?? [] as $teaches) {
                $contentsOrObjective = $dataUtil->getStudyContentsOrObjectiveByIdAndUrl(
                    $teaches->identifier,
                    $teaches->inDefinedTermSet->url
                );
                if ($contentsOrObjective instanceof StudyContentsInterface) {
                    $levelOrSubject = $contentsOrObjective->getRoot()->getProxiedObject();
                    if ($levelOrSubject instanceof EducationalLevelInterface) {
                        $data[EducationalData::TRANSVERSAL_COMPETENCES][] = $contentsOrObjective;
                    } elseif ($levelOrSubject instanceof EducationalSubjectInterface) {
                        $data[EducationalData::STUDY_CONTENTS][] = $contentsOrObjective;
                    } else {
                        throw new \Exception();
                    }
                } elseif ($contentsOrObjective instanceof StudyObjectiveInterface) {
                    $data[EducationalData::STUDY_OBJECTIVES][] = $contentsOrObjective;
                } else {
                    throw new \Exception();
                }
            }
            return $data;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return all encapsulated record items.
     *
     * @return array
     */
    protected function getEncapsulatedRecordItems(): array
    {
        // Implementation for XML items in 'material' elements.
        $items = [];
        $xml = $this->getXmlRecord();
        foreach ($xml->material as $item) {
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Return ID for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item.
     *
     * @return string
     */
    protected function getEncapsulatedRecordId($item): string
    {
        // Implementation for XML items with ID specified in an 'identifier' element
        return (string)$item->identifier;
    }

    /**
     * Return format for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item
     *
     * @return string
     */
    protected function getEncapsulatedRecordFormat($item): string
    {
        return 'Curatedrecord';
    }

    /**
     * Return record driver instance for an encapsulated curated record.
     *
     * @param \SimpleXMLElement $item Curated record item XML
     *
     * @return CuratedRecord
     *
     * @see ContainerFormatTrait::getEncapsulatedRecordDriver()
     */
    protected function getCuratedrecordDriver(\SimpleXMLElement $item): CuratedRecord
    {
        $driver = $this->recordDriverManager->get('CuratedRecord');

        $encapsulatedRecord = $this->recordLoader->load(
            (string)$item->identifier,
            DEFAULT_SEARCH_BACKEND,
            true
        );

        $data = [
            'id' => (string)$item->identifier,
            'record' => $encapsulatedRecord,
            'title' => $encapsulatedRecord->getTitle(),
            'position' => (int)$item->position,
            'notes' => (string)$item->comment,
        ];

        $driver->setRawData($data);

        return $driver;
    }
}
