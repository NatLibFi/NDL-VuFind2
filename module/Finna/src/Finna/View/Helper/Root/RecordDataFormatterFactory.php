<?php
/**
 * Factory for record driver data formatting view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2016.
 * Copyright (C) The National Library of Finland 2017.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * Factory for record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class RecordDataFormatterFactory
{
    /**
     * Create the helper.
     *
     * @return RecordDataFormatter
     */
    public function __invoke()
    {
        $helper = new \VuFind\View\Helper\Root\RecordDataFormatter();
        $helper->setDefaults('core', $this->getDefaultCoreSpecs());
        $helper->setDefaults('description', $this->getDefaultDescriptionSpecs());
        return $helper;
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Original Work', 'getOriginalWork', 'data-forwardFields.phtml',
            [
                'context' => ['class' => 'recordOriginalWork']
            ]
        );
        $spec->setTemplateLine(
            'Published in', 'getContainerTitle', 'data-containerTitle.phtml',
            [
                'context' => ['class' => 'record-container-link']
            ]
        );
        $spec->setLine(
            'New Title', 'getNewerTitles', 'data-titles.phtml',
            [
                'context' => ['class' => 'recordNextTitles']
            ]
        );
        $spec->setLine(
            'Previous Title', 'getPreviousTitles', 'data-titles.phtml',
            [
                'context' => ['class' => 'recordPrevTitles']
            ]
        );
        $spec->setTemplateLine(
            'Contributors', 'getNonPresenterAuthors', 'data-contributors.phtml',
            [
                'context' => ['class' => 'recordAuthors']
            ]
        );
        $spec->setTemplateLine(
            'Actors', 'getPresenters', 'data-actors.phtml',
            [
                'context' => ['class' => 'recordPresenters']
            ]
        );
        $spec->setTemplateLine(
            'Uncredited Actors', 'getPresenters', 'data-uncreditedActors.phtml',
            [
                'context' => ['class' => 'recordPresenters']
            ]
        );
        $spec->setTemplateLine(
            'Assistants', 'getAssistants', 'data-assistants.phtml',
            [
                'context' => ['class' => 'record-assistants']
            ]
        );
        $spec->setTemplateLine(
            'Description', 'getDescription', 'data-forwardFields.phtml',
            [
                'context' => ['class' => 'recordDescription']
            ]
        );
        $spec->setTemplateLine(
            'Press Reviews', 'getPressReview', 'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-press-review']
            ]
        );
        $spec->setTemplateLine(
            'Music', 'getMusicInfo', 'data-forwardFields.phtml',
            [
                'context' => ['class' => 'record-music']
            ]
        );
        $spec->setTemplateLine(
            'Projected Publication Date', 'getProjectedPublicationDate',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'coreProjectedPublicationDate']
            ]
        );
        $spec->setTemplateLine(
            'Dissertation Note', 'getDissertationNote', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'coreDissertationNote']
            ]
        );
        $spec->setTemplateLine(
            'link_Arvosteltu teos', 'getOtherLinks', 'data-getOtherLinks.phtml',
            [
                'labelFunction'  => function ($data) {
                    return $data[0]['heading'];
                },
                'context' => ['class' => 'recordOtherLink']
            ]
        );
        $spec->setTemplateLine(
            'Presenters', 'getPresenters', 'data-presenters.phtml',
            [
                'context' => ['class' => 'recordPresenters']
            ]
        );
        $spec->setTemplateLine(
            'Other Titles', 'getAlternativeTitles', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordAltTitles']
            ]
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            [
                'helperMethod' => 'getFormatList',
                'context' => ['class' => 'recordFormat']
            ]
        );
        $spec->setTemplateLine(
            'Physical Description', 'getPhysicalDescriptions',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'physicalDescriptions']
            ]
        );
        $spec->setTemplateLine(
            'Language', 'getLanguages', 'data-transEsc.phtml',
            [
                'context' => ['class' => 'recordLanguage']
            ]
        );
        $spec->setTemplateLine(
            'original_work_language', 'getOriginalLanguages', 'data-transEsc.phtml',
            [
                'context' => ['class' => 'originalLanguage']
            ]
        );
        $spec->setTemplateLine(
            'Item Description', 'getGeneralNotes', 'data-forwardFields.phtml',
            [
                'context' => ['class' => 'recordDescription']
            ]
        );
        $spec->setTemplateLine(
            'Subject Detail', 'getSubjectDetails', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setTemplateLine(
            'Subject Place', 'getSubjectPlaces', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setLine(
            'Subject Date', 'getSubjectDates', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setLine(
            'Subject Actor', 'getSubjectActors', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setTemplateLine(
            'Organisation', 'getInstitutions', 'data-organisation.phtml',
            [
                'context' => ['class' => 'recordInstitution']
            ]
        );
        $spec->setTemplateLine(
            'Collection', 'getCollections', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordCollection']
            ]
        );
        $spec->setTemplateLine(
            'Inventory ID', 'getIdentifier', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordIdentifier']
            ]
        );
        $spec->setTemplateLine(
            'Measurements', 'getMeasurements', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordMeasurements']
            ]
        );
        $spec->setTemplateLine(
            'Inscriptions', 'getInscriptions', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordInscriptions']
            ]
        );
        $spec->setTemplateLine(
            'Other Classification', 'getFormatClassifications',
            'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordClassifications']
            ]
        );
        $spec->setTemplateLine(
            'Other ID', 'getLocalIdentifiers', 'data-implodeSubject.phtml',
            [
                'context' => ['class' => 'recordIdentifiers']
            ]
        );
        $spec->setTemplateLine(
            'mainFormat', 'getEvents', 'data-mainFormat.phtml',
            [
                'context' => ['class' => 'recordHide']
            ]
        );
        $spec->setTemplateLine(
            'Archive Origination', 'getOrigination', 'data-origination.phtml',
            [
                'context' => ['class' => 'record-origination']
            ]
        );
        $spec->setTemplateLine(
            'Archive', 'isPartOfArchiveSeries', 'data-archive.phtml',
            [
                'context' => ['class' => 'recordHierarchyLinks']
            ]
        );
        $spec->setTemplateLine(
            'Archive Series', 'isPartOfArchiveSeries', 'data-archiveSeries.phtml',
            [
                'context' => ['class' => 'recordSeries']
            ]
        );
        $spec->setTemplateLine(
            'Published', 'getDateSpan', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedDateSpan']
            ]
        );
        $spec->setLine(
            'Unit ID', 'getUnitID', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordReferenceCode']
            ]
        );
        $spec->setTemplateLine(
            'Authors', 'getNonPresenterAuthors', 'data-authors.phtml',
            [
                'context' => ['class' => 'recordAuthors']
            ]
        );
        $spec->setTemplateLine(
            'Publisher', 'getPublicationDetails', 'data-publicationDetails.phtml',
            [
                'context' => ['class' => 'recordDescription']
            ]
        );
        $spec->setTemplateLine(
            'Published', 'getPublicationDetails', 'data-publicationDetails.phtml',
            [
                'context' => ['class' => 'recordPublications']
            ]
        );
        $spec->setTemplateLine(
            'Projected Publication Date', 'getProjectedPublicationDate',
            'data-transEsc.phtml',
            [
                'context' => ['class' => 'coreProjectedPublicationDate']
            ]
        );
        $spec->setTemplateLine(
            'Dissertation Note', 'getDissertationNote', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'coreDissertationNote']
            ]
        );
        $spec->setTemplateLine(
            'Edition', 'getEdition', 'data-edition.phtml',
            [
                'context' => ['class' => 'recordEdition']
            ]
        );
        $spec->setTemplateLine(
            'Series', 'getSeries', 'data-series.phtml',
            [
                'context' => ['class' => 'recordSeries']
            ]
        );
        $spec->setTemplateLine(
            'Classification', 'getClassifications', 'data-classification.phtml',
            [
                'context' => ['class' => 'recordClassifications']
            ]
        );
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setTemplateLine(
            'Manufacturer', 'getManufacturer', 'data-transEsc.phtml',
            [
                'context' => ['class' => 'recordManufacturer']
            ]
        );
        $spec->setTemplateLine(
            'Production', 'getProducers', 'data-producers.phtml',
            [
                'context' => ['class' => 'recordManufacturer']
            ]
        );
        $spec->setTemplateLine(
            'Funding', 'getFunders', 'data-fundingDistribution.phtml',
            [
                'context' => ['class' => 'record-funders']
            ]
        );
        $spec->setTemplateLine(
            'Distribution', 'getDistributors', 'data-fundingDistribution.phtml',
            [
                'context' => ['class' => 'record-distributors']
            ]
        );
        $spec->setTemplateLine(
            'Additional Information', 'getTitleStatement', 'data-addInfo.phtml',
            [
                'context' => ['class' => 'recordTitleStatement']
            ]
        );
        $spec->setTemplateLine(
            'Genre', 'getGenres', 'data-genres.phtml',
            [
                'context' => ['class' => 'recordGenres']
            ]
        );
        $spec->setTemplateLine(
            'child_records', 'getChildRecordCount', 'data-childRecords.phtml',
            [
                'allowZero' => false,
                'context' => ['class' => 'recordComponentParts']
            ]
        );
        $spec->setTemplateLine(
            'Online Access', true, 'data-onlineAccess.phtml',
            [
                'context' => ['class' => 'webResource']
            ]
        );
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml',
            [
                'context' => ['class' => 'extendedRelatedItems']
            ]
        );
        $spec->setTemplateLine(
            'Keywords', 'getKeywords', 'data-keywords.phtml',
            [
                'context' => ['class' => 'record-keywords']
            ]
        );
        $spec->setTemplateLine(
            'Education Programs', 'getEducationPrograms', 'data-education.phtml',
            [
                'context' => ['class' => 'record-education-programs']
            ]
        );
        $spec->setTemplateLine(
            'Publication Frequency', 'getPublicationFrequency',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedFrequency']
            ]
        );
        $spec->setTemplateLine(
            'Playing Time', 'getPlayingTimes', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedPlayTime']
            ]
        );
        $spec->setTemplateLine(
            'Color', 'getColor', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-color']
            ]
        );
        $spec->setTemplateLine(
            'Sound', 'getSound', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'record-sound']
            ]
        );
        $spec->setTemplateLine(
            'Aspect Ratio', 'getAspectRatio', 'data-escapeHtml',
            [
                'context' => ['class' => 'record-aspect-ratio']
            ]
        );
        $spec->setTemplateLine(
            'Media Format', 'getSystemDetails', 'data-escapeHtml',
            [
                'context' => ['class' => 'extendedSystem']
            ]
        );
        $spec->setTemplateLine(
            'Audience', 'getTargetAudienceNotes', 'data-escapeHtml',
            [
                'context' => ['class' => 'extendedAudience']
            ]
        );
        $spec->setTemplateLine(
            'Awards', 'getAwards', 'data-forwardFields.phtml',
            [
                'context' => ['class' => 'extendedAwards']
            ]
        );
        $spec->setTemplateLine(
            'Production Credits', 'getProductionCredits', 'data-escapeHtml',
            [
                'context' => ['class' => 'extendedCredits']
            ]
        );
        $spec->setTemplateLine(
            'Bibliography', 'getBibliographyNotes', 'data-transEsc.phtml',
            [
                'context' => ['class' => 'extendedBibliography']
            ]
        );
        $spec->setTemplateLine(
            'ISBN', 'getISBNs', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedISBNs']
            ]
        );
        $spec->setTemplateLine(
            'ISSN', 'getISSNs', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedISSNs']
            ]
        );
        $spec->setTemplateLine(
            'DOI', 'getCleanDOI', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extended-doi']
            ]
        );
        $spec->setTemplateLine(
            'Related Items', 'getRelationshipNotes', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedRelatedItems']
            ]
        );
        $spec->setTemplateLine(
            'Access Restrictions', 'getAccessRestrictions', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedAccess']
            ]
        );
        $spec->setTemplateLine(
            'Terms of Use', 'getTermsOfUse', 'data-termsOfUse.phtml',
            [
                'context' => ['class' => 'extendedTermsOfUse']
            ]
        );
        $spec->setTemplateLine(
            'Finding Aid', 'getFindingAids', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'extendedFindingAids']
            ]
        );
        $spec->setTemplateLine(
            'Publication_Place', 'getHierarchicalPlaceNames',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'publicationPlace']
            ]
        );
        $spec->setTemplateLine(
            'Author Notes', true, 'data-authorNotes.phtml',
            [
                'context' => ['class' => 'extendedAuthorNotes']
            ]
        );
        $spec->setTemplateLine(
            'Location', 'getPhysicalLocations', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordPhysicalLocation']
            ]
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in the description tab.
     *
     * @return array
     */
    public function getDefaultDescriptionSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();
        $spec->setLine('Summary', 'getSummary');
        $spec->setLine('Published', 'getDateSpan');
        $spec->setLine('Item Description', 'getGeneralNotes');
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setLine('Format', 'getSystemDetails');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine('ISBN', 'getISBNs');
        $spec->setLine('ISSN', 'getISSNs');
        $spec->setLine('DOI', 'getCleanDOI');
        $spec->setLine('Related Items', 'getRelationshipNotes');
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
        return $spec->getArray();
    }
}
