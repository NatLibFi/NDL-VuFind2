<?php
/**
 * Factory for record driver data formatting view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2016.
 * Copyright (C) The National Library of Finalnd 2017.
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
        $spec->setLine(
            'Original Work', 'getOriginalWork', null
        );
        $spec->setTemplateLine(
            'Published in', 'getContainerTitle', 'data-containerTitle.phtml'
        );
        $spec->setLine(
            'New Title', 'getNewerTitles', null, ['recordLink' => 'title']
        );
        $spec->setLine(
            'Previous Title', 'getPreviousTitles', null, ['recordLink' => 'title']
        );
        $spec->setTemplateLine(
            'Assistants', 'getAssistants', 'data-assistants.phtml'
        );
        $spec->setTemplateLine(
            'Description', 'getDescription', 'data-description.phtml'
        );
        $spec->setTemplateLine(
            'Press Reviews', 'getPressReview', 'data-pressReview.phtml'
        );
        $spec->setTemplateLine(
            'Music', 'getMusicInfo', 'data-music.phtml'
        );
        $spec->setLine(
            'Projected Publication Date', 'getProjectedPublicationDate'
        );
        $spec->setLine(
            'Dissertation Note', 'getDissertationNote'
        );
        //TODO fix this template
        $spec->setTemplateLine(
            'link_', 'getOtherLinks', 'data-getOtherLinks.phtml',
            [
                'labelFunction'  => function ($data) {
                    $data = "link_Arvosteltu teos";
                    return $data;
                },
            ]
        );
        $spec->setTemplateLine(
            'Presenters', 'getPresenters', 'data-presenters.phtml'
        );
        $spec->setTemplateLine(
            'Other Titles', 'getAlternativeTitles', 'data-alternativeTitles.phtml'
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Subject Detail', 'getSubjectDetails');
        $spec->setLine('Subject Place', 'getSubjectPlaces');
        $spec->setLine('Subject Date', 'getSubjectDates');
        $spec->setLine('Subject Actor', 'getSubjectActors');
        //TODO organisaatio ei saa näkyy aina fiksaantuu varmaan tr-luokalla
        $spec->setTemplateLine(
            'Organisation', 'getInstitutions', 'data-organisation.phtml'
        );
        $spec->setLine('Collection', 'getCollection');
        $spec->setLine('Inventory ID', 'getIdentifier');
        $spec->setLine('Measurements', 'getMeasurements');
        $spec->setLine('Inscriptions', 'getInscriptions');
        $spec->setLine('Other Classification', 'getFormatClassifications');
        $spec->setLine('Other ID', 'getLocalIdentifiers');
        //TODO fix this template:
        $spec->setTemplateLine(
            '', 'getMainFormat', 'data-mainFormat.phtml',
            [
                'labelFunction'  => function ($data) {
                    return false;
                },
            ]
        );
        $spec->setTemplateLine(
            'Archive Origination', 'getOrigination', 'data-origination.phtml'
        );
        $spec->setTemplateLine(
            'Archive', 'isPartOfArchiveSeries', 'data-archive.phtml'
        );
        $spec->setTemplateLine(
            'Archive Series', 'isPartOfArchiveSeries', 'data-archiveSeries.phtml'
        );
        $spec->setLine('Unit ID', 'getUnitID');
        $spec->setTemplateLine(
            'Authors', 'getNonPresenterAuthors', 'data-authors.phtml'
        );
        $spec->setTemplateLine(
            'Language', 'getLanguages', 'data-language.phtml'
        );
        $spec->setTemplateLine(
            'original_work_language', 'getOriginalLanguages',
            'data-originalLanguage.phtml'
        );
        $spec->setTemplateLine(
            'Item Description', 'getGeneralNotes', 'data-itemDescription.phtml'
        );
        $spec->setTemplateLine(
            'Published', 'getPublicationDetails', 'data-publicationDetails.phtml'
        );
        $spec->setTemplateLine(
            'Publisher', 'getPublicationDetails', 'data-publisher.phtml'
        );
        $spec->setLine('Projected Publication Date', 'getProjectedPublicationDate');
        $spec->setLine('Dissertation Note', 'getDissertationNote');
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setTemplateLine(
            'Series', 'getSeries', 'data-series.phtml'
        );
        $spec->setTemplateLine(
            'Classification', 'getClassification', 'data-classification.phtml'
        );
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setLine('Manufacturer', 'getManufacturer');
        $spec->setTemplateLine(
            'Production', 'getProducers', 'data-producers.phtml'
        );
        $spec->setTemplateLine(
            'Funding', 'getFunders', 'data-funding.phtml'
        );
        $spec->setTemplateLine(
            'Distribution', 'getDistributors', 'data-distribution.phtml'
        );
        $spec->setTemplateLine(
            'Additional Information', 'getTitleStatement', 'data-addInfo.phtml'
        );
        $spec->setTemplateLine(
            'Genre', 'getGenres', 'data-genres.phtml'
        );
        $spec->setLine('Location', 'getPhysicalLocations');
        $spec->setTemplateLine(
            'child_records', 'getChildRecordCount', 'data-childRecords.phtml',
            ['allowZero' => false]
        );
        $spec->setTemplateLine(
            'Online Access', true, 'data-onlineAccess.phtml'
        );
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setTemplateLine(
            'Keywords', 'getKeywords', 'data-keywords.phtml'
        );
        $spec->setTemplateLine(
            'Education Programs', 'getEducationPrograms', 'data-education.phtml'
        );
        $spec->setLine('Published', 'getDateSpan');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setTemplateLine('Color', 'getColor', 'data-colors.phtml');
        $spec->setTemplateLine('Sound', 'getSound', 'data-sound.phtml');
        $spec->setLine('Aspect Ratio', 'getAspectRatio');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine('ISBN', 'getISBNs');
        $spec->setLine('ISSN', 'getISSNs');
        $spec->setLine('DOI', 'getCleanDOI');
        $spec->setTemplateLine(
            'Terms Of Use', 'getTermsOfUse', 'data-termsOfUse.phtml'
        );
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
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
