<?php
/**
 * Record driver data formatting view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2016.
 * Copyright (C) The National Library of Finland 2017-2022.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
namespace Finna\View\Helper\Root;

use Exception;
use Finna\View\Helper\Root\RecordDataFormatter\FieldGroupBuilder;
use VuFind\RecordDriver\AbstractBase as RecordDriver;

/**
 * Record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class RecordDataFormatter extends \VuFind\View\Helper\Root\RecordDataFormatter
{
    /**
     * Filter unnecessary fields from Marc records.
     *
     * @param array $coreFields data to filter.
     *
     * @return array
     */
    public function filterMarcFields($coreFields)
    {
        $include = [
            'Access',
            'Additional Information',
            'Age Limit',
            'Audience',
            'Audience Characteristics',
            'Author Notes',
            'Awards',
            'Bibliography',
            'child_records',
            'Classification',
            'Copyright Notes',
            'Creator Characteristics',
            'DOI',
            'Dissertation Note',
            'Education Programs',
            'Event Notice',
            'Finding Aid',
            'First Lyrics',
            'Genre',
            'Hardware',
            'ISBN',
            'ISSN',
            'Inventory ID',
            'Item Description',
            'Keywords',
            'Language',
            'Language Notes',
            'Manufacturer',
            'Methodology',
            'Music Compositions Extended',
            'New Title',
            'Notated Music Format',
            'Notes',
            'Original Version Notes',
            'original_work_language',
            'Other Links',
            'Other Titles',
            'Physical Description',
            'Place of Origin',
            'Playing Time',
            'Presenters',
            'Previous Title',
            'Production Credits',
            'Projected Publication Date',
            'Publication Frequency',
            'Publication_Place',
            'Publish date',
            'Publisher',
            'Publisher or Distributor Number',
            'Record Links',
            'Related Items',
            'Related Places',
            'Scale',
            'Series',
            'Source of Acquisition',
            'Standard Codes',
            'Standard Report Number',
            'subjects_extended',
            'System Format',
            'Terms of Use',
            'Time Period',
            'Time Period of Creation',
            'Trade Availability Note',
            'Uncontrolled Title',
            'Uniform Title',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from Lido records.
     *
     * @param array $coreFields data to filter.
     *
     * @return array
     */
    public function filterLidoFields($coreFields)
    {
        $include = [
            'Additional Information',
            'Audience',
            'Author Notes',
            'Available Online',
            'Awards',
            'Bibliography',
            'child_records',
            'Collection',
            'DOI',
            'Edition',
            'Education Programs',
            'Events',
            'Finding Aid',
            'Format',
            'Genre',
            'ISBN',
            'ISSN',
            'Inscriptions',
            'Introduction',
            'Inventory ID',
            'Item Description',
            'Keywords',
            'Language',
            'lido_editions',
            'Measurements',
            'New Title',
            'Organisation',
            'original_work_language',
            'Other Classification',
            'Other Classifications',
            'Other ID',
            'Parent Archive',
            'Parent Collection',
            'Parent Purchase Batch',
            'Parent Series',
            'Parent Unclassified Entity',
            'Parent Work',
            'Physical Description',
            'Playing Time',
            'Presenters',
            'Previous Title',
            'Production Credits',
            'Publication Frequency',
            'Publication_Place',
            'Publications',
            'Published in',
            'Record Links',
            'Related Items',
            'Related Places',
            'Series',
            'Subject Actor',
            'Subject Date',
            'Subject Detail',
            'Subject Place',
            'SubjectsWithoutPlaces',
            'System Format',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from QDC records.
     *
     * @param array $coreFields data to filter.
     *
     * @return array
     */
    public function filterQDCFields($coreFields)
    {
        $include = [
            'Access',
            'Additional Information',
            'Audience',
            'Author Notes',
            'Awards',
            'Bibliography',
            'child_records',
            'Contained In',
            'DOI',
            'Edition',
            'Education Programs',
            'Finding Aid',
            'Genre',
            'ISBN',
            'ISSN',
            'Inventory ID',
            'Item Description',
            'Keywords',
            'Language',
            'New Title',
            'original_work_language',
            'Physical Description',
            'Physical Medium',
            'Playing Time',
            'Presenters',
            'Previous Title',
            'Production Credits',
            'Publication Frequency',
            'Publication_Place',
            'Published in',
            'Related Items',
            'Related Places',
            'Series',
            'Subjects',
            'System Format',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from Lrmi records
     *
     * @param array $coreFields data to filter
     *
     * @return array
     */
    public function filterLrmiFields($coreFields)
    {
        $include = [
            'Access',
            'Access Restrictions',
            'Accessibility Feature',
            'Accessibility Hazard',
            'Actors',
            'Additional Information',
            'Audience',
            'Author Notes',
            'Authors',
            'Awards',
            'Bibliography',
            'child_records',
            'Contained In',
            'DOI',
            'Edition',
            'Education Programs',
            'Educational Level',
            'Educational Role',
            'Educational Subject',
            'Educational Use',
            'Extent',
            'Finding Aid',
            'Format',
            'Genre',
            'ISBN',
            'ISSN',
            'Identifiers',
            'Inventory ID',
            'Item Description',
            'Item Description FWD',
            'Keywords',
            'Language',
            'Learning Resource Type',
            'New Title',
            'Objective and Content',
            'Online Access',
            'Organisation',
            'original_work_language',
            'Other Titles',
            'Physical Description',
            'Physical Medium',
            'Playing Time',
            'Presenters',
            'Previous Title',
            'Production Credits',
            'Publication Frequency',
            'Publication_Place',
            'Publish date',
            'Published',
            'Published in',
            'Publisher',
            'Record Links',
            'Related Items',
            'Related Materials',
            'Related Places',
            'Series',
            'Source Collection',
            'Subjects',
            'System Format',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from EAD records.
     *
     * @param array $coreFields data to filter.
     *
     * @return array
     */
    public function filterEADFields($coreFields)
    {
        $include = [
            'Access Restrictions',
            'Additional Information',
            'Archive',
            'Archive File',
            'Archive Origination',
            'Archive Series',
            'Audience',
            'Author Notes',
            'Authors',
            'Awards',
            'Bibliography',
            'DOI',
            'Date',
            'Edition',
            'Education Programs',
            'Extent',
            'Finding Aid',
            'Format',
            'Genre',
            'ISBN',
            'ISSN',
            'Item Description',
            'Keywords',
            'Language',
            'Location',
            'New Title',
            'Other Titles',
            'Playing Time',
            'Presenters',
            'Previous Title',
            'Production Credits',
            'Publication Frequency',
            'Publication_Place',
            'Publish date',
            'Publisher',
            'Record Links',
            'Related Items',
            'Related Places',
            'Subjects',
            'System Format',
            'Unit ID',
            'original_work_language',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from EAD records.
     *
     * @param array $coreFields data to filter.
     *
     * @return array
     */
    public function filterEAD3Fields($coreFields)
    {
        $include = [
            'Access Restrictions',
            'Access Restrictions Extended',
            'Additional Information Extended',
            'Appraisal',
            'Archive',
            'Archive File',
            'Archive Origination',
            'Archive Relations',
            'Archive Series',
            'Audience',
            'Author Notes',
            'Authors',
            'Awards',
            'Bibliography', 'Container Information',
            'Content Description',
            'DOI',
            'Dates',
            'Edition',
            'Education Programs',
            'Extent',
            'Finding Aid Extended',
            'Format',
            'Genre',
            'ISBN',
            'ISSN',
            'Item Description',
            'Item History',
            'Keywords',
            'Language',
            'Location',
            'Material Arrangement',
            'Material Condition',
            'New Title',
            'original_work_language',
            'Other Related Material',
            'Other Titles',
            'Playing Time',
            'Presenters',
            'Previous Title',
            'Production Credits',
            'Publication Frequency',
            'Publication_Place',
            'Publish date',
            'Publisher',
            'Related Items',
            'Related Materials',
            'Related Places',
            'subjects_extended',
            'System Format',
            'Unit IDs',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from Primo records.
     *
     * @param array $coreFields data to filter.
     *
     * @return array
     */
    public function filterPrimoFields($coreFields)
    {
        $include = [
            'Access',
            'Additional Information',
            'Audience',
            'Author Notes',
            'Awards',
            'Bibliography',
            'child_records',
            'DOI',
            'Description FWD',
            'Edition',
            'Finding Aid',
            'ISBN',
            'ISSN',
            'Item Description',
            'Language',
            'New Title',
            'Physical Description',
            'Playing Time',
            'Previous Title',
            'Production Credits',
            'Publication Frequency',
            'Publication_Place',
            'Publish date',
            'Record Links',
            'Related Items',
            'Series',
            'Source Collection',
            'Subjects',
            'System Format',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from Forward records.
     *
     * @param array $coreFields data to filter.
     *
     * @return array
     */
    public function filterForwardFields($coreFields)
    {
        $include = [
            'Access',
            'Actors',
            'Additional Information',
            'Age Limit',
            'Archive Films',
            'Aspect Ratio',
            'Audience',
            'Author Notes',
            'Awards',
            'Bibliography',
            'Broadcasting Dates',
            'child_records',
            'Color',
            'DOI',
            'Description FWD',
            'Distribution',
            'Education Programs',
            'Exterior Images',
            'Film Copies',
            'Film Festivals',
            'Filming Date',
            'Filming Location Notes',
            'Finding Aid',
            'Foreign Distribution',
            'Funding',
            'Genre',
            'ISBN',
            'ISSN',
            'Inspection Details',
            'Interior Images',
            'Inventory ID',
            'Item Description FWD',
            'Keywords',
            'Language',
            'Movie Thanks',
            'Music',
            'New Title',
            'Number of Viewers',
            'Online Access',
            'Original Work',
            'original_work_language',
            'Other Screenings',
            'Physical Description',
            'Playing Time',
            'Premiere Night',
            'Premiere Theaters',
            'Press Reviews',
            'Previous Title',
            'Production',
            'Production Costs',
            'Production Credits',
            'Publication Frequency',
            'Publication_Place',
            'Publish date',
            'Published',
            'Record Links',
            'Related Items',
            'Related Places',
            'Secondary Authors',
            'Series',
            'Sound',
            'Studios',
            'Subjects',
            'System Format',
        ];
        return array_intersect_key($coreFields, array_flip($include));
    }

    /**
     * Filter unnecessary fields from EAD-collection records.
     *
     * @param array  $coreFields data to filter.
     * @param string $type       Collection type (ead|ead3)
     *
     * @return array
     *
     * @throws Exception If trying to access record type without collection support
     */
    public function filterCollectionFields($coreFields, $type = 'ead')
    {
        switch ($type) {
        case 'ead':
            return $this->filterEADFields($coreFields);
        case 'ead3':
            return $this->filterEAD3Fields($coreFields);
        case 'lido':
            return $this->filterLidoFields($coreFields);
        default:
            throw new Exception("Collection for record type $type doesn't exist.");
        }
    }

    /**
     * Helper method for getting a spec of field groups from FieldGroupBuilder.
     *
     * @param array  $groups        Array specifying the groups. See
     *                              FieldGroupBuilder::addGroup() for details.
     * @param array  $lines         All lines used in the groups. If this contains
     *                              lines not specified in $groups, all unused lines
     *                              will be appended as their own group.
     * @param string $template      Default group template to use if not specified
     *                              for a group (optional, set to null to use the
     *                              default value).
     * @param array  $options       Additional options to use if not specified for a
     *                              group (optional, set to null to use the default
     *                              value). See FieldGroupBuilder::addGroup() for
     *                              details.
     * @param array  $unusedOptions Additional options for the unused lines group
     *                              (optional, set to null to use the default value).
     *                              See FieldGroupBuilder::addGroup()
     *                              for details.
     *
     * @return array
     */
    public function getGroupedFields(
        $groups,
        $lines,
        $template = null,
        $options = null,
        $unusedOptions = null
    ) {
        $fieldGroups = new FieldGroupBuilder();
        $fieldGroups->setGroups(
            $groups,
            $lines,
            $template ?? 'core-field-group-fields.phtml',
            $options ?? [],
            $unusedOptions ?? []
        );
        return $fieldGroups->getArray();
    }

    /**
     * Create formatted key/value data based on a record driver and grouped
     * field spec.
     *
     * @param RecordDriver $driver Record driver object.
     * @param array        $groups Grouped formatting specification.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupedData(RecordDriver $driver, array $groups)
    {
        // Apply the group spec.
        $result = [];
        foreach ($groups as $group) {
            if (!empty($group['skipGroup'])) {
                continue;
            }
            $lines = $group['lines'];
            $data = $this->getData($driver, $lines);
            if (empty($data)) {
                continue;
            }
            // Render the fields in the group as the value for the group.
            $value = $this->renderRecordDriverTemplate(
                $driver,
                $data,
                ['template' => $group['template']]
            );
            $result[] = [
                'label' => $group['label'],
                'value' => $value,
                'context' => $group['context'],
            ];
        }
        return $result;
    }
}
