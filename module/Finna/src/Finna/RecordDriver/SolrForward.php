<?php
/**
 * Model for FORWARD records in Solr.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016-2017.
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
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace Finna\RecordDriver;

/**
 * Model for FORWARD records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrForward extends \VuFind\RecordDriver\SolrDefault
    implements \Laminas\Log\LoggerAwareInterface
{
    use Feature\SolrFinnaTrait;
    use Feature\SolrForwardTrait {
        Feature\SolrForwardTrait::getAllImages insteadof Feature\SolrFinnaTrait;
    }
    use Feature\FinnaUrlCheckTrait;
    use \VuFind\Log\LoggerAwareTrait;

    protected $howmanyloops = 0;

    /**
     * Non-presenter author relator codes.
     *
     * @var array
     */
    protected $nonPresenterAuthorRelators = [
        'a00', 'a01', 'a03', 'a06', 'a50', 'a99',
        'b13',
        'd01', 'd02', 'd99',
        'e02', 'e03', 'e04', 'e05', 'e06', 'e08',
        'f01', 'f02', 'f99',
        'cmp', 'cph', 'exp', 'fds', 'fmp', 'rce', 'wst', 'oth', 'prn',
        // These are copied from Marc
        'act', 'anm', 'ann', 'arr', 'acp', 'ar', 'ard', 'aft', 'aud', 'aui', 'aus',
        'bjd', 'bpd', 'cll', 'ctg', 'chr', 'cng', 'clb', 'clr', 'cwt', 'cmm', 'com',
        'cpl', 'cpt', 'cpe', 'ccp', 'cnd', 'cos', 'cot', 'coe', 'cts', 'ctt', 'cte',
        'ctb', 'crp', 'cst', 'cov', 'cur', 'dnc', 'dtc', 'dto', 'dfd', 'dft', 'dfe',
        'dln', 'dpc', 'dsr', 'dis', 'drm', 'edt', 'elt', 'egr', 'etr', 'fac',
        'fld', 'flm', 'frg', 'ilu', 'ill', 'ins', 'itr', 'ivr', 'ldr', 'lsa', 'led',
        'lil', 'lit', 'lie', 'lel', 'let', 'lee', 'lbt', 'lgd', 'ltg', 'lyr', 'mrb',
        'mte', 'msd', 'mus', 'nrt', 'opn', 'org', 'pta', 'pth', 'prf', 'pht', 'ptf',
        'ptt', 'pte', 'prt', 'pop', 'prm', 'pro', 'pmn', 'prd', 'prg', 'pdr', 'pbd',
        'ppt', 'ren', 'rpt', 'rth', 'rtm', 'res', 'rsp', 'rst', 'rse', 'rpy', 'rsg',
        'rev', 'rbr', 'sce', 'sad', 'scr', 'scl', 'spy', 'std', 'sng', 'sds', 'spk',
        'stm', 'str', 'stl', 'sht', 'ths', 'trl', 'tyd', 'tyg', 'vdg', 'voc', 'wde',
        'wdc', 'wam'
    ];

    /**
     * Primary author relator codes (mapped)
     *
     * @var array
     */
    protected $primaryAuthorRelators = ['drt'];

    /**
     * Presenter author relator codes.
     *
     * @var array
     */
    protected $presenterAuthorRelators = [
        'e01', 'e99', 'cmm', 'a99', 'oth'
    ];

    /**
     * Relator to RDA role mapping.
     *
     * @var array
     */
    protected $roleMap = [
        'A00' => 'oth',
        'A03' => 'aus',
        'A06' => 'cmp',
        'A50' => 'aud',
        'A99' => 'oth',
        'B13' => 'Sound editor',
        'D01' => 'fmp',
        'D02' => 'drt',
        'E01' => 'act',
        'E04' => 'cmm',
        'E10' => 'pro',
        'F01' => 'cng',
        'F02' => 'flm'
    ];

    /**
     * ELONET role to RDA role mapping.
     *
     * @var array
     */
    protected $elonetRoleMap = [
        'dialogi' => 'aud',
        'lavastus' => 'std',
        'lavastaja' => 'std',
        'puvustus' => 'cst',
        'tuotannon suunnittelu' => 'prs',
        'tuotantopäällikkö' => 'pmn',
        'muusikko' => 'mus',
        'selostaja' => 'cmm',
        'valokuvaaja' => 'pht',
        'valonmääritys' => 'lgd',
        'äänitys' => 'rce',
        'dokumentti-esiintyjä' => 'prf',
        'kreditoimaton-dokumentti-esiintyjä' => 'prf',
        'dokumentti-muutesiintyjät' => 'oth'
    ];

    /**
     * Role attributes
     *
     * @var array
     */
    protected $roleAttributes = [
        'elokuva-elotekija-rooli',
        'elokuva-elonayttelija-rooli',
        'elokuva-eloesiintyja-maare',
        'elokuva-elonayttelijakokoonpano-tehtava'
    ];

    /**
     * Uncredited role attributes
     *
     * @var array
     */
    protected $uncreditedRoleAttributes = [
        'elokuva-elokreditoimatonnayttelija-rooli',
        'elokuva-elokreditoimatonesiintyja-maare'
    ];

    /**
     * Uncredited creator attributes
     *
     * @var array
     */
    protected $uncreditedCreatorAttributes = [
        'elokuva-elokreditoimatontekija-nimi'
    ];

    

    /**
     * Content descriptors
     *
     * @var array
     */
    protected $contentDescriptors = [
        'väkivalta' => 'content_descriptor_violence',
        'seksi' => 'content_descriptor_sexual_content',
        'päihde' => 'content_descriptor_drug_use',
        'ahdistus' => 'content_descriptor_anxiety'
    ];

    /**
     * Age restrictions
     *
     * @var array
     */
    protected $ageRestrictions = [
        'S' => 'age_rating_for_all_ages',
        'T' => 'age_rating_for_all_ages',
        '7' => 'age_rating_7',
        '12' => 'age_rating_12',
        '16' => 'age_rating_16',
        '18' => 'age_rating_18'
    ];

    /**
     * Unwanted video warnings
     *
     * @var array
     */
    protected $filteredWarnings = [
        'K'
    ];

    /**
     * Inspection attributes
     *
     * @var array
     */
    protected $inspectionAttributes = [
        'elokuva-tarkastus-tarkastusnro' => 'number',
        'elokuva-tarkastus-tarkastamolaji' => 'inspectiontype',
        'elokuva-tarkastus-pituus' => 'length',
        'elokuva-tarkastus-veroluokka' => 'taxclass',
        'elokuva-tarkastus-ikaraja' => 'agerestriction',
        'elokuva-tarkastus-formaatti' => 'format',
        'elokuva-tarkastus-osalkm' => 'part',
        'elokuva-tarkastus-tarkastuttaja' => 'office',
        'elokuva-tarkastus-kesto' => 'runningtime',
        'elokuva-tarkastus-tarkastusaihe' => 'subject',
        'elokuva-tarkastus-perustelut' => 'reason',
        'elokuva-tarkastus-muuttiedot' => 'additional',
        'elokuva-tarkastus-tarkastusilmoitus' => 'notification',
        'elokuva-tarkastus-tarkastuselin' => 'inspector'
    ];

    /**
     * Roles to not display
     *
     * @var array
     */
    protected $filteredRoles = [
        'prf',
        'oth'
    ];

    /**
     * Uncredited name attributes
     *
     * @var array
     */
    protected $uncreditedNameAttributes = [
        'elokuva-elokreditoimatontekija-nimi',
        'elokuva-elokreditoimatonnayttelija-nimi'
    ];

    /**
     * Descriptions
     *
     * @var array
     */
    protected $roleDescriptions = [
        'elokuva-elotekija-selitys',
        'elokuva-elonayttelija-selitys',
        'elokuva-elokreditoimatonnayttelija-selitys',
        'elokuva-elokreditoimatontekija-selitys'
    ];


    /**
     * Identification strings for where should the author be saved in the results
     * 
     * @var array
     */
    protected $presenterIdentifications = [
        'elonet_henkilo|act|credited' => ['presenters' => 'credited'],
        'elonet_henkilo|act|uncredited' => ['presenters' => 'uncredited'],
        'elonet_kokoonpano|any_value|credited'
            => ['presenters' => 'actingEnsemble'],
        'elonet_henkilo|no_value|credited' => ['presenters' => 'performer'],
        'elonet_henkilo|no_value|uncredited'
            => ['presenters' => 'uncreditedPerformer'],
        'any_value|no_value|credited' => ['presenters' => 'other'],
        'no_value|muutesiintyjät|credited' => ['presenters' => 'other'],
        'elonet_kokoonpano|no_value|credited'
            => ['presenters' => 'performingEnsemble'],
        'no_value|avustajat|credited' => ['presenters' => 'assistant'],
    ];

    /**
     * Identification strings for where should the author be saved in the results
     * 
     * @var array
     */
    protected $nonPresenterIdentifications = [
        'elonet_kokoonpano|any_value|credited'
            => ['nonPresenterSecondaryAuthors' => 'uncreditedEnsembles'],
        'any_value|any_value|uncredited'
            => ['nonPresenterSecondaryAuthors' => 'uncredited'],
        'any_value|any_value|credited'
            => ['nonPresenterSecondaryAuthors' => 'credited'],
        'elonet_henkilo|any_value|credited'
            => ['nonPresenterSecondaryAuthors' => 'credited']
    ];

    /**
     * Values to preserve when forming identification string
     * 
     * @var array
     */
    protected $valuesToPreserve = [
        'no_value',
        'act',
        'elonet_henkilo',
        'elonet_kokoonpano',
        'muutesiintyjät',
        'avustajat'
    ];

        /**
     * Mappings to save production attribute as a string
     * 
     * @var array
     */
    protected $productionAttributeMappings = [
        'elokuva-kuvasuhde' => 'AspectRatio',
        'elokuva-alkupvari' => 'Color',
        'elokuva-alkupvarijarjestelma' => 'ColorSystem',
        'elokuva-alkuperaisteos' => 'OriginalWork',
        'elokuva-alkupkesto' => 'PlayingTimes',
        'elokuva-alkupaani' => 'Sound',
        'elokuva-alkupaanijarjestelma' => 'SoundSystem',
        'elokuva-tuotantokustannukset' => 'ProductionCost',
        'elokuva-teatterikopioidenlkm' => 'NumberOfCopies',
        'elokuva-kuvausaika' => 'FilmingDate',
        'elokuva-arkistoaineisto' => 'ArchiveFilms'
    ];

    /**
     * Mappings to save production elements as values
     * 
     * @var array
     */
    protected $productionEventMappings = [
        'elokuva_laji2fin' => 'Type',
        'elokuva_huomautukset' => 'GeneralNotes',
        'elokuva_musiikki' => 'MusicInfo',
        'elokuva_lehdistoarvio' => 'PressReview',
        'elokuva_ulkokuvat' => 'Exteriors',
        'elokuva_sisakuvat' => 'Interiors',
        'elokuva_studiot' => 'Studios',
        'elokuva_kuvauspaikkahuomautus' => 'LocationNotes',
    ];

    /**
     * Mappings to get proper broadcasting information
     * 
     * @var array
     */
    protected $broadcastingInfoMappings = [
        'elokuva-elotelevisioesitys-esitysaika' => 'time',
        'elokuva-elotelevisioesitys-paikka' => 'place',
        'elokuva-elotelevisioesitys-katsojamaara' => 'viewers'
    ];

    /**
     * Mappings to get proper festival subjects
     * 
     * @var array
     */
    protected $festivalSubjectHeaders = [
        'elokuva-elofestivaaliosallistuminen-aihe' => true
    ];

    /**
     * Mappings to get proper distributor headers
     * 
     * @var array
     */
    protected $foreignDistributorHeaders = [
        'elokuva-eloulkomaanmyynti-levittaja' => true
    ];

    /**
     * Mappings to get proper other screening headers
     * 
     * @var array
     */
    protected $otherScreeningHeaders = [
        'elokuva-muuesitys-aihe' => true
    ];

    /**
     * Mappings to get inspection values
     * 
     * @var array
     */
    protected $inspectionAttributeHeaders = [
        'elokuva-tarkastus-tarkastusnro' => 'number',
        'elokuva-tarkastus-tarkastuselin' => true,
        'elokuva-tarkastus-tarkastusilmoitus' => true
    ];

    /**
     * Headers for access restrictions
     * 
     * @var array
     */
    protected $accessRestrictionHeaders = [
        'finna-kayttooikeus' => 1
    ];

    /**
     * Record metadata
     *
     * @var array
     */
    protected $lazyRecordXML;

    /**
     * Nonpresenter authors cache
     *
     * @var array
     */
    protected $nonPresenterAuthorsCache = null;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $mainConfig     VuFind main configuration (omit
     * for built-in defaults)
     * @param \Laminas\Config\Config $recordConfig   Record-specific configuration
     * file (omit to use $mainConfig as $recordConfig)
     * @param \Laminas\Config\Config $searchSettings Search-specific configuration
     * file
     */
    public function __construct(
        $mainConfig = null,
        $recordConfig = null,
        $searchSettings = null
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
        $this->searchSettings = $searchSettings;
    }

    /**
     * Return access restriction notes for the record.
     *
     * @return array
     */
    public function getAccessRestrictions()
    {
        $events = $this->getProductionEvents();
        return array_keys($events['AccessRestrictions'] ?? []);
    }

    /**
     * Return type of access restriction for the record.
     *
     * @param string $language Language
     *
     * @return mixed array with keys:
     *   'copyright'   Copyright (e.g. 'CC BY 4.0')
     *   'link'        Link to copyright info, see IndexRecord::getRightsLink
     *   or false if no access restriction type is defined.
     */
    public function getAccessRestrictionsType($language)
    {
        $events = $this->getProductionEvents();
        foreach (array_keys($events['AccessRestrictions'] ?? []) as $type) {
            $result = ['copyright' => $type];
            if ($link = $this->getRightsLink($type, $language)) {
                $result['link'] = $link;
            }
            return $result;
        }
        return false;
    }

    /**
     * Return all subject headings
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading
     * - type: heading type
     * - source: source vocabulary
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false)
    {
        $results = [];
        foreach ($this->getRecordXML()->SubjectTerms as $subjectTerms) {
            foreach ($subjectTerms->Term as $term) {
                if (!$extended) {
                    $results[] = [$term];
                } else {
                    $results[] = [
                        'heading' => [$term],
                        'type' => '',
                        'source' => ''
                    ];
                }
            }
        }
        return $results;
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        return $this->getVideoUrls();
    }

    /**
     * Get an array of alternative titles for the record.
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        $xml = $this->getRecordXML();
        $identifyingTitle = (string)$xml->IdentifyingTitle;
        $result = [];
        foreach ($xml->Title as $title) {
            $titleText = (string)$title->TitleText;
            if ($titleText == $identifyingTitle) {
                continue;
            }
            $rel = $title->TitleRelationship;
            if ($rel && $type = $rel->attributes()->{'elokuva-elonimi-tyyppi'}) {
                $titleText .= " ($type)";
            } elseif ((string)$rel === 'working') {
                $titleText .= ' (' . $this->translate('working title') . ')';
            } elseif ($rel && (string)$rel == 'translated') {
                $lang = $title->TitleText->attributes()->lang;
                if ($lang) {
                    $lang = $this->translate($lang);
                    $titleText .= " ($lang)";
                }
            }
            $result[] = $titleText;
        }
        return $result;
    }

    /**
     * Get award notes for the record.
     *
     * @return array
     */
    public function getAwards()
    {
        $results = [];
        foreach ($this->getRecordXML()->Award as $award) {
            $results[] = (string)$award;
        }
        return $results;
    }

    /**
     * Return aspect ratio
     *
     * @return string
     */
    public function getAspectRatio()
    {
        $events = $this->getProductionEvents();
        return $events['AspectRatio'] ?? '';
    }

    /**
     * Return type
     *
     * @return string
     */
    public function getType()
    {
        $events = $this->getProductionEvents();
        return trim(implode(', ', $events['Type'] ?? []));
    }

    /**
     * Return color
     *
     * @return string
     */
    public function getColor()
    {
        $events = $this->getProductionEvents();
        return $events['Color'] ?? '';
    }

    /**
     * Return color system
     *
     * @return string
     */
    public function getColorSystem()
    {
        $events = $this->getProductionEvents();
        return $events['ColorSystem'] ?? '';
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        $xml = $this->getRecordXML();
        return !empty($xml->CountryOfReference->Country->RegionName)
            ? (string)$xml->CountryOfReference->Country->RegionName : '';
    }

    /**
     * Return descriptions
     *
     * @return array
     */
    public function getDescription()
    {
        $locale = $this->getLocale();

        $result = $this->getDescriptionData('Content description', $locale);
        if (empty($result)) {
            $result = $this->getDescriptionData('Content description');
        }
        return $result;
    }

    /**
     * Get distributors
     *
     * @return array
     */
    public function getDistributors()
    {
        $authors = $this->getAuthors();
        return $authors['distributors'] ?? [];
    }

    /**
     * Get funders
     *
     * @return array
     */
    public function getFunders()
    {
        $authors = $this->getAuthors();
        return $authors['funders'] ?? [];
    }

    /**
     * Get general notes on the record.
     *
     * @return array
     */
    public function getGeneralNotes()
    {
        $events = $this->getProductionEvents();
        return $events['GeneralNotes'] ?? '';
    }

    /**
     * Return image rights.
     *
     * @param string $language       Language
     * @param bool   $skipImageCheck Whether to check that images exist
     *
     * @return mixed array with keys:
     *   'copyright'   Copyright (e.g. 'CC BY 4.0') (optional)
     *   'description' Human readable description (array)
     *   'link'        Link to copyright info
     *   or false if the record contains no images
     */
    public function getImageRights($language, $skipImageCheck = false)
    {
        if (!$skipImageCheck && !$this->getAllImages()) {
            return false;
        }

        $rights = [];
        if ($type = $this->getAccessRestrictionsType($language)) {
            $rights['copyright'] = $type['copyright'];
            if (isset($type['link'])) {
                $rights['link'] = $type['link'];
            }
        }

        return isset($rights['copyright']) ? $rights : false;
    }

    /**
     * Return music information
     *
     * @return string
     */
    public function getMusicInfo()
    {
        $events = $this->getProductionEvents();
        $results = $events['MusicInfo'] ?? [];
        if ($result = reset($results) ?? '') {
            $result = preg_replace('/(\d+\. )/', '<br/>\1', $result);
            if (strncmp($result, '<br/>', 5) == 0) {
                $result = substr($result, 5);
            }
        }
        return $result;
    }

    /**
     * Get presenters as an assoc array
     *
     * @return array
     */
    public function getPresenters(): array
    {
        $authors = $this->getAuthors();
        return $authors['presenters'] ?? [];
    }

    /**
     * Get all primary authors apart from presenters
     *
     * @return array
     */
    public function getNonPresenterPrimaryAuthors()
    {
        $authors = $this->getAuthors();
        return $authors['primaryAuthors']  ?? [];
    }


    /**
     * Get all authors apart from presenters
     *
     * @return array
     */
    public function getNonPresenterAuthors($primary = null): array
    {
        $authors = $this->getAuthors();
        return $authors['nonPresenters'] ?? [];
    }

    /**
     * Get all secondary authors apart from presenters
     *
     * @return array
     */
    public function getNonPresenterSecondaryAuthors()
    {
        $authors = $this->getAuthors();
        return $authors['nonPresenterSecondaryAuthors'] ?? [];
    }

    /**
     * Loop through all the authors and save them into a cache
     * 
     * @param array specifications Specifications for the authors
     * 
     * @return array
     */
    public function getAuthors(): array
    {
        $cacheKey = __FUNCTION__;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $xml = $this->getRecordXML();
        $idx = 0;
        $results = [
            'all' => [],
            'primaryAuthors' => [],
            'producers' => [],
        ];
        $primaryAuthors = [];

        $createIdentificationString = function (
            string $type,
            string $role,
            bool $uncredited
        ): string {
            return implode(
                '|',
                [
                    in_array(
                        $type,
                        $this->valuesToPreserve
                    ) ? $type : 'any_value',
                    in_array(
                        $role,
                        $this->valuesToPreserve
                    ) ? $role : 'any_value',
                    $uncredited === true ? 'uncredited' : 'credited'
                ]
            );
        };

        foreach ($xml->HasAgent as $agent) {
            $result = [
                'tag' => ((string)$agent['elonet-tag'] ?? ''),
                'name' => '',
                'role' => '',
                'id' => '',
                'type' => '',
                'roleName' => '',
                'description' => '',
                'uncredited' => '',
                'idx' => ''
            ];

            $tag = ($agent['elonet-tag'] ?? '');
            $identification = [];
            if (!empty($agent->Activity)) {
                $activity = $agent->Activity;
                $relator = (string)$activity;
                $primary = $relator === 'D02';
                if (null === ($role = $this->getAuthorRole($agent, $relator))) {
                    continue;
                }
                if (in_array($role, $this->filteredRoles)) {
                    $result['role'] = '';
                } else {
                    $result['role'] = $role;
                }
                $attributes = $activity->attributes();
                foreach ($attributes as $key => $value) {
                    $result[$key] = (string)$value;
                }
                $result['relator'] = (string)$activity;
            }
            if (!empty($agent->AgentName)) {
                $agentName = $agent->AgentName;
                $attributes = $agentName->attributes();
                $result['name'] = (string)$agentName;
                foreach ($attributes as $key => $value) {
                    $valueString = (string)$value;
                    $result[$key] = $valueString;
                    if (empty($result['name'])) {
                        if (in_array($key, $this->uncreditedNameAttributes)) {
                            $result['name'] = $valueString;
                        }
                    }
                    if (in_array($key, $this->roleAttributes)) {
                        $result['roleName'] = $valueString;
                        continue;
                    }
                    if (in_array($key, $this->uncreditedRoleAttributes)) {
                        $result['roleName'] = $valueString;
                        $result['uncredited'] = true;
                        continue;
                    }
                    if (in_array($key, $this->uncreditedCreatorAttributes)) {
                        $result['uncredited'] = true;
                        continue;
                    }
                    if (in_array($key, $this->roleDescriptions)) {
                        $result['description'] = $valueString;
                        continue;
                    }
                }
            }
            if (!empty($agent->AgentIdentifier)) {
                $authType = (string)$agent->AgentIdentifier->IDTypeName;
                $idValue = (string)$agent->AgentIdentifier->IDValue;
                $authId = "{$authType}_{$idValue}";
                $result['id'] = $authId;
                $result['type'] = $authType;
            }
            $idx++;
            $result['idx'] = $primary ? $idx : 10000 * $idx;

            $isUncredited = ($result['uncredited'] ?? false) === true;
            $type = $result['type'] ?? 'no_value';
            // Create identification string for saving to correct array
            $id = $createIdentificationString(
                $type,
                $role,
                $isUncredited
            );

            $lRelator = mb_strtolower($result['relator'] ?? '');
            // Get primary authors
            if (in_array($lRelator, $this->primaryAuthorRelators)) {
                $results['primaryAuthors'][] = $result;
            }
            // Save presenter authors
            if (in_array($lRelator, $this->presenterAuthorRelators)) {
                if ($storage = $this->presenterIdentifications[$id] ?? []) {
                    foreach($storage as $key => $value) {
                        if (!isset($results[$key])) {
                            $results[$key] = [$value => ['presenters' => []]];
                        }
                        $results[$key][$value]['presenters'][] = $result;
                    }
                }
            }
            // Save nonpresenter authors
            if (in_array($lRelator, $this->nonPresenterAuthorRelators)) {
                if ($storage = $this->nonPresenterIdentifications[$id] ?? []) {
                    foreach($storage as $key => $value) {

                        if (!isset($results[$key])) {
                            $results[$key] = [$value => []];
                        }
                        $results[$key][$value][] = $result;
                    }
                }
                if (!isset($results['nonPresenters'])) {
                    $results['nonPresenters'] = [];
                }
                $results['nonPresenters'][] = $result;
            }

            // Save producers
            if ('E10' === ($result['finna-activity-code'] ?? '')
                || isset($result['elokuva-elotuotantoyhtio'])
            ) {
                $results['producers'][] = $result;
            }

            // Save distributors
            if ('fds' === ($result['finna-activity-code'] ?? '')) {
                $result['date'] = $result['elokuva-elolevittaja-vuosi'] ?? '';
                $result['method']
                    = $result['elokuva-elolevittaja-levitystapa'] ?? '';
                $results['distributors'][] = $result;
            }

            // Save funders
            if ('fnd' === ($result['finna-activity-code'] ?? '')) {
                $result['amount'] = $result['elokuva-elorahoitusyhtio-summa'] ?? '';
                $result['fundingType']
                    = $result['elokuva-elorahoitusyhtio-rahoitustapa'] ?? '';
                $results['funders'][] = $result;
            }


            $results['all'][] = $result;
        }
        return $this->cache[$cacheKey] = $results;
    }

    /**
     * Get online URLs
     *
     * @param bool $raw Whether to return raw data
     *
     * @return array
     */
    public function getOnlineURLs($raw = false)
    {
        $videoUrls = $this->getVideoUrls();
        $urls = [];
        foreach ($videoUrls as $videoUrl) {
            $urls[] = json_encode($videoUrl);
        }
        if ($videoUrls && !empty($this->fields['online_urls_str_mv'])) {
            // Filter out video URLs
            foreach ($this->fields['online_urls_str_mv'] as $urlJson) {
                $url = json_decode($urlJson, true);
                if ($videoUrls && strpos($url['url'], 'elonet.fi') > 0
                    && strpos($url['url'], '/video/') > 0
                ) {
                    continue;
                }
                $urls[] = $urlJson;
            }
        }
        return $raw ? $urls : $this->mergeURLArray($urls, true);
    }

    /**
     * Return original work information
     *
     * @return string
     */
    public function getOriginalWork()
    {
        $events = $this->getProductionEvents();
        return $events['OriginalWork'] ?? '';
    }

    /**
     * Return playing times
     *
     * @return array
     */
    public function getPlayingTimes()
    {
        $events = $this->getProductionEvents();
        $str = $events['PlayingTimes'] ?? '';
        return $str ? [$str] : [];
    }

    /**
     * Return press review
     *
     * @return string
     */
    public function getPressReview()
    {
        $events = $this->getProductionEvents();
        $results = $events['PressReview'] ?? [];
        if ($result = reset($results)) {
            return $result;
        }
        return '';
    }

    /**
     * Get producers
     *
     * @return array
     */
    public function getProducers()
    {
        $authors = $this->getAuthors();
        return $authors['producers'] ?? [];
    }

    /**
     * Return sound
     *
     * @return string
     */
    public function getSound()
    {
        $events = $this->getProductionEvents();
        return $events['Sound'] ?? '';
    }

    /**
     * Return sound system
     *
     * @return string
     */
    public function getSoundSystem()
    {
        $events = $this->getProductionEvents();
        return $events['SoundSystem'] ?? '';
    }

    /**
     * Return summary
     *
     * @return array
     */
    public function getSummary()
    {
        $locale = $this->getLocale();

        $result = $this->getDescriptionData('Synopsis', $locale);
        if (empty($result)) {
            $result = $this->getDescriptionData('Synopsis');
        }
        return $result;
    }

    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     * objects are normally constructed by Record Driver objects using data
     * passed in from a Search Results object.  The exact nature of the data may
     * vary depending on the data source -- the important thing is that the
     * Record Driver + Search Results objects work together correctly.
     *
     * @return void
     */
    public function setRawData($data)
    {
        parent::setRawData($data);
        $this->lazyRecordXML = null;
    }

    /**
     * Return full record as filtered XML for public APIs.
     *
     * @return string
     */
    public function getFilteredXML()
    {
        $record = clone $this->getRecordXML();
        $remove = [];
        foreach ($record->ProductionEvent as $event) {
            $attributes = $event->attributes();
            if (isset($attributes->{'elonet-tag'})
                && 'lehdistoarvio' === (string)$attributes->{'elonet-tag'}
            ) {
                $remove[] = $event;
            }
        }
        foreach ($remove as $node) {
            unset($node[0]);
        }
        return $record->asXMl();
    }

    /**
     * Get all original records as a SimpleXML object
     *
     * @return SimpleXMLElement The record as SimpleXML
     */
    protected function getAllRecordsXML()
    {
        if ($this->lazyRecordXML === null) {
            $xml = new \SimpleXMLElement($this->fields['fullrecord']);
            $records = (array)$xml->children();
            $records = reset($records);
            $this->lazyRecordXML = is_array($records) ? $records : [$records];
        }
        return $this->lazyRecordXML;
    }

    /**
     * Get descriptions, optionally only in given language
     *
     * @param string $type     Description type
     * @param string $language Optional language code
     *
     * @return array
     */
    protected function getDescriptionData($type, $language = null)
    {
        $results = [];
        foreach ($this->getRecordXML()->ContentDescription as $description) {
            if (null !== $language && (string)$description->Language !== $language) {
                continue;
            }
            if ((string)$description->DescriptionType == $type
                && !empty($description->DescriptionText)
            ) {
                $results[] = (string)$description->DescriptionText;
            }
        }
        return $results;
    }


    protected function getProductionEvents(): array
    {
        $cacheKey = __FUNCTION__;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $results = [
            'BroadcastingInfo' => [],
            'FestivalInfo' => [],
            'ForeignDistribution' => [],
            'OtherScreenings' => [],
            'InspectionDetails' => [],
            'AccessRestrictions' => []
        ];
        $xml = $this->getRecordXML();
        foreach ($xml->ProductionEvent as $event) {
            $type = (string)($event->ProductionEventType ?? '');
            $regionName = (string)($event->Region->RegionName ?? '');
            $dateText = (string)($event->DateText ?? '');
            // Get premiere theater information
            if ($type === 'PRE') {
                $theater = $regionName;
                $results['PremiereTheater'] = explode(';', $theater);
                $results['PremiereTime'] = $dateText;
            }

            $attributes = $event->ProductionEventType->attributes();
            $broadcastingResult = [];
            $inspectionResult = [];
            foreach ($attributes as $key => $value) {;
                $stringValue = (string)$value;
                // Get production attribute
                if ($storage = $this->productionAttributeMappings[$key] ?? '') {
                    $results[$storage] = $stringValue;
                }
                // Get broadcasting information
                if ($info = $this->broadcastingInfoMappings[$key] ?? '') {
                    $broadcastingResult[$info] = $stringValue;
                }
                // Get festival info
                if ($festival = $this->festivalSubjectHeaders[$key] ?? '') {
                    $results['FestivalInfo'][] = [
                        'name' => $stringValue,
                        'region' => $regionName,
                        'date' => $dateText
                    ];
                }
                // Get foreign distribution info
                if ($distributor = $this->foreignDistributorHeaders[$key] ?? '') {
                    $results['ForeignDistribution'][] = [
                        'name' => $stringValue,
                        'region' => $regionName
                    ];
                }
                // Get other screening info
                if ($screening = $this->otherScreeningHeaders[$key] ?? '') {
                    $results['OtherScreenings'][] = [
                        'name' => $stringValue,
                        'region' => $regionName,
                        'date' => $dateText
                    ];
                }
                // Get inspection detail
                if ($inspection = $this->inspectionAttributes[$key] ?? '') {
                    $inspectionResult[$inspection] = $stringValue;
                }
                // Get access restriction details
                if ($restriction = $this->accessRestrictionHeaders[$key] ?? '') {
                    $results['AccessRestrictions'][$stringValue] = 1;
                }
            }
            // Check if we have found something to save
            if ($broadcastingResult) {
                $results['BroadcastingInfo'][] = $broadcastingResult;
            }
            if ($inspectionResult) {
                if (strpos($dateText, '0000') === false) {
                    $inspectionResult['date'] = $dateText;
                }
                $results['InspectionDetails'][] = $inspectionResult;
            }
            $children = $event->children();
            foreach ($children as $childKey => $childValue) {
                if ($storage = $this->productionEventMappings[$childKey] ?? []) {
                    $results[$storage][] = (string)$childValue;
                }
            }
        }
        return $this->cache[$cacheKey] = $results;
    }

    /**
     * Get the original main record as a SimpleXML object
     *
     * @return SimpleXMLElement The record as SimpleXML
     */
    protected function getRecordXML()
    {
        $records = $this->getAllRecordsXML();
        return reset($records);
    }

    /**
     * Get video URLs
     *
     * @return array
     */
    protected function getVideoUrls()
    {
        // Get video URLs, if any
        if (empty($this->recordConfig->Record->video_sources)) {
            return [];
        }
        $source = $this->getSource();
        $sourceConfigs = [];
        $sourcePriority = 0;
        foreach ($this->recordConfig->Record->video_sources as $current) {
            $settings = explode('|', $current, 4);
            if (!isset($settings[2]) || $source !== $settings[0]) {
                continue;
            }
            $sourceConfigs[] = [
                'mediaType' => $settings[1],
                'src' => $settings[2],
                'sourceTypes' => explode(',', $settings[3] ?? 'mp4'),
                'priority' => $sourcePriority++
            ];
        }
        if (empty($sourceConfigs)) {
            return [];
        }
        $posterSource = $this->recordConfig->Record->poster_sources[$source] ?? '';

        $videoUrls = [];
        foreach ($this->getAllRecordsXML() as $xml) {
            foreach ($xml->Title as $title) {
                if (!isset($title->TitleText)) {
                    continue;
                }

                $videoUrl = (string)$title->TitleText;
                $videoSources = [];
                $sourceType = strtolower(pathinfo($videoUrl, PATHINFO_EXTENSION));

                $poster = '';
                $videoType = 'elokuva';
                $description = '';
                $warnings = [];
                if (isset($title->PartDesignation->Value)) {
                    $attributes = $title->PartDesignation->Value->attributes();
                    if (!empty($attributes['video-tyyppi'])) {
                        $videoType = (string)$attributes->{'video-tyyppi'};
                    }
                    // Use video type as the default description since the additional
                    // information may contain long descriptive texts.
                    $description = $videoType ? $videoType
                        : (string)$attributes->{'video-lisatieto'};

                    $posterFilename = (string)$title->PartDesignation->Value;
                    if ($posterFilename) {
                        $poster = str_replace(
                            '{filename}',
                            $posterFilename,
                            $posterSource
                        );
                    }

                    // Check for warnings
                    if (!empty($attributes->{'video-rating'})) {
                        $tmpWarnings
                            = explode(', ', (string)$attributes->{'video-rating'});
                        // Translate to english, for universal usage
                        foreach ($tmpWarnings as $warning) {
                            if (!in_array($warning, $this->filteredWarnings)) {
                                $warnings[]
                                    = $this->contentDescriptors[$warning]
                                    ?? $this->ageRestrictions[$warning]
                                    ?? $warning;
                            }
                        }
                    }
                }

                //If there is no ProductionEventType set, continue
                if (!isset($xml->ProductionEvent->ProductionEventType)) {
                    continue;
                }
                $eventAttrs = $xml->ProductionEvent->ProductionEventType
                    ->attributes();

                // Lets see if this video has a vimeo-id
                $vimeo = (string)$eventAttrs->{'vimeo-id'};
                $vimeo_url = $this->recordConfig->Record->vimeo_url;
                if (!empty($vimeo) && !empty($vimeo_url)) {
                    $src = str_replace(
                        '{videoid}',
                        $vimeo,
                        $vimeo_url
                    );
                    $videoUrls[] = [
                        'url' => $src,
                        'posterUrl' => $poster,
                        // Include both 'text' and 'desc' for online and normal urls
                        'text' => $description ?: $videoType,
                        'desc' => $description ?: $videoType,
                        'source' => $source,
                        'embed' => 'iframe',
                        'warnings' => $warnings
                    ];
                }

                $url = (string)$eventAttrs->{'elokuva-elonet-materiaali-video-url'};

                foreach ($sourceConfigs as $config) {
                    if (!in_array($sourceType, $config['sourceTypes'])) {
                        continue;
                    }
                    $src = str_replace(
                        '{videoname}',
                        $videoUrl,
                        $config['src']
                    );
                    $videoSources[] = [
                        'src' => $src,
                        'type' => $config['mediaType'],
                        'priority' => $config['priority']
                    ];
                }

                if (empty($videoSources)) {
                    continue;
                }

                usort(
                    $videoSources,
                    function ($a, $b) {
                        return $a['priority'] - $b['priority'];
                    }
                );

                if ($this->urlBlocked($url, $description)) {
                    continue;
                }

                $videoUrls[] = [
                    'url' => $url,
                    'posterUrl' => $poster,
                    'videoSources' => $videoSources,
                    // Include both 'text' and 'desc' for online and normal urls
                    'text' => $description ? $description : $videoType,
                    'desc' => $description ? $description : $videoType,
                    'source' => $source,
                    'embed' => 'video',
                    'warnings' => $warnings
                ];
            }
        }
        return $videoUrls;
    }

    /**
     * Return production cost
     *
     * @return string
     */
    public function getProductionCost()
    {
        $events = $this->getProductionEvents();
        return $events['ProductionCost'] ?? '';
    }

    /**
     * Return premier night theaters and places
     *
     * @return array
     */
    public function getPremiereTheaters()
    {
        $events = $this->getProductionEvents();
        return $events['PremiereTheater'] ?? [];
    }

    /**
     * Return opening night time
     *
     * @return string
     */
    public function getPremiereTime()
    {
        $events = $this->getProductionEvents();
        return $events['PremiereTime'] ?? [];
    }

    /**
     * Return television broadcasting dates, channels and amount of viewers
     *
     * @return array
     */
    public function getBroadcastingInfo()
    {
        $events = $this->getProductionEvents();
        return $events['BroadcastingInfo'] ?? [];
    }

    /**
     * Return filmfestival attendance information
     *
     * @return array
     */
    public function getFestivalInfo()
    {
        $events = $this->getProductionEvents();
        return $events['FestivalInfo'] ?? [];
    }

    /**
     * Return foreign distributors and countries
     *
     * @return array
     */
    public function getForeignDistribution()
    {
        $events = $this->getProductionEvents();
        return $events['ForeignDistribution'] ?? [];
    }

    /**
     * Return number of film copies
     *
     * @return string
     */
    public function getNumberOfCopies()
    {
        $events = $this->getProductionEvents();
        return $events['NumberOfCopies'] ?? '';
    }

    /**
     * Return other screening occasions
     *
     * @return array
     */
    public function getOtherScreenings()
    {
        $events = $this->getProductionEvents();
        return $events['OtherScreenings'] ?? [];
    }

    /**
     * Return movie inspection details
     *
     * @return array
     */
    public function getInspectionDetails()
    {
        $events = $this->getProductionEvents();
        return $events['InspectionDetails'] ?? [];
    }

    /**
     * Return movie Age limit
     *
     * Get Age limit from last inspection's details
     *
     * @return string AgeLimit
     */
    public function getAgeLimit()
    {
        $inspectionDetails = $this->getInspectionDetails();
        $currentDate = 0;
        $currentLimit = null;
        foreach ($inspectionDetails as $inspection) {
            if (empty($inspection['agerestriction'])) {
                continue;
            }

            // Use this age restriction if we don't have an earlier one or the
            // inspection is at least as new as the earlier one.
            $inspectionDate = isset($inspection['date'])
                ? strtotime($inspection['date']) : 0;
            if (null === $currentLimit || $inspectionDate >= $currentDate) {
                $currentLimit = $inspection['agerestriction'];
                $currentDate = $inspectionDate;
            }
        }
        return $currentLimit;
    }

    /**
     * Return exteriors
     *
     * @return array
     */
    public function getExteriors()
    {
        $events = $this->getProductionEvents();
        return $events['Exteriors'] ?? '';
    }

    /**
     * Return interiors
     *
     * @return array
     */
    public function getInteriors()
    {
        $events = $this->getProductionEvents();
        return $events['Interiors'] ?? '';
    }

    /**
     * Return studios
     *
     * @return array
     */
    public function getStudios()
    {
        $events = $this->getProductionEvents();
        return $events['Studios'] ?? '';
    }

    /**
     * Return location notes
     *
     * @return string
     */
    public function getLocationNotes()
    {
        $events = $this->getProductionEvents();
        return $events['LocationNotes'] ?? '';
    }

    /**
     * Return filming date
     *
     * @return string
     */
    public function getFilmingDate()
    {
        $events = $this->getProductionEvents();
        return $events['FilmingDate'] ?? '';
    }

    /**
     * Return archive films
     *
     * @return string
     */
    public function getArchiveFilms()
    {
        $events = $this->getProductionEvents();
        return $events['ArchiveFilms'] ?? '';
    }

    /**
     * Return an XML representation of the record using the specified format.
     * Return false if the format is unsupported.
     *
     * @param string     $format     Name of format to use (corresponds with OAI-PMH
     * metadataPrefix parameter).
     * @param string     $baseUrl    Base URL of host containing VuFind (optional;
     * may be used to inject record URLs into XML when appropriate).
     * @param RecordLink $recordLink Record link helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return mixed         XML, or false if format unsupported.
     */
    public function getXML($format, $baseUrl = null, $recordLink = null)
    {
        if ('oai_forward' === $format) {
            return $this->fields['fullrecord'];
        }
        return parent::getXML($format, $baseUrl, $recordLink);
    }

    /**
     * Convert author relator to role.
     *
     * @param SimpleXMLNode $agent   Agent
     * @param string        $relator Agent relator
     *
     * @return string
     */
    protected function getAuthorRole($agent, $relator)
    {
        $normalizedRelator = mb_strtoupper($relator, 'UTF-8');
        $role = $this->roleMap[$normalizedRelator] ?? $relator;

        $attributes = $agent->Activity->attributes();
        if (in_array(
            $normalizedRelator,
            ['A00', 'A08', 'A99', 'D99', 'E04', 'E99']
        )
        ) {
            if (!empty($attributes->{'elokuva-elolevittaja'})
            ) {
                return null;
            }
            if (!empty($attributes->{'elokuva-elotuotantoyhtio'})
                || !empty($attributes->{'elokuva-elorahoitusyhtio'})
                || !empty($attributes->{'elokuva-elolaboratorio'})
            ) {
                return null;
            }
            if (!empty($attributes->{'finna-activity-text'})) {
                $role = (string)$attributes->{'finna-activity-text'};
                if (isset($this->elonetRoleMap[$role])) {
                    $role = $this->elonetRoleMap[$role];
                }
            }
        }

        return $role;
    }
}
