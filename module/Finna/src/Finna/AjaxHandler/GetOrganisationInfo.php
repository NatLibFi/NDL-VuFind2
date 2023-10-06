<?php

/**
 * AJAX handler for getting organisation info.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2023.
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
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\AjaxHandler;

use Finna\OrganisationInfo\OrganisationInfo;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Cache\Manager as CacheManager;
use VuFind\Cookie\CookieManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Session\Settings as SessionSettings;

use function count;
use function in_array;
use function is_array;

/**
 * AJAX handler for getting organisation info.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetOrganisationInfo extends \VuFind\AjaxHandler\AbstractBase implements
    TranslatorAwareInterface,
    \Laminas\Log\LoggerAwareInterface,
    \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFindHttp\HttpServiceAwareTrait;

    public const COOKIE_NAME = 'organisationInfoId';

    /**
     * Cookie manager
     *
     * @var CookieManager
     */
    protected $cookieManager;

    /**
     * Organisation info
     *
     * @var OrganisationInfo
     */
    protected $organisationInfo;

    /**
     * Cache manager
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * Facet configuration
     *
     * @var array
     */
    protected $facetConfig;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss               Session settings
     * @param CookieManager     $cookieManager    ILS connection
     * @param OrganisationInfo  $organisationInfo Organisation info
     * @param CacheManager      $cacheManager     Cache manager
     * @param RendererInterface $renderer         View renderer
     * @param array             $facetConfig      Facet configuration
     */
    public function __construct(
        SessionSettings $ss,
        CookieManager $cookieManager,
        OrganisationInfo $organisationInfo,
        CacheManager $cacheManager,
        RendererInterface $renderer,
        array $facetConfig
    ) {
        $this->sessionSettings = $ss;
        $this->cookieManager = $cookieManager;
        $this->organisationInfo = $organisationInfo;
        $this->cacheManager = $cacheManager;
        $this->renderer = $renderer;
        $this->facetConfig = $facetConfig;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $element = $params->fromQuery('element');
        $sectors = array_filter((array)$params->fromQuery('sectors', []));
        $buildings = array_filter(explode(',', $params->fromQuery('buildings', '')));
        if (!($id = $params->fromQuery('id'))) {
            return $this->handleError('getOrganisationInfo: missing id');
        }

        // Back-compatibility; allow e.g. lib/pub:
        $sectors = array_map(
            function ($s) {
                [$sector] = explode('/', $s, 2);
                return $sector;
            },
            $sectors
        );

        switch ($element) {
            case 'info-location-selection':
                $result = $this->getInfoAndLocationSelection(
                    $id,
                    $this->getLocationIdFromCookie($id),
                    $sectors,
                    $buildings,
                    (bool)$params->fromQuery('consortiumInfo', false)
                );
                break;
            case 'location-details':
                if (!($locationId = $params->fromQuery('locationId'))) {
                    if (!($locationId = $this->getLocationIdFromCookie($id))) {
                        return $this->handleError('getOrganisationInfo: missing location id');
                    }
                } else {
                    $this->setLocationIdCookie($id, $locationId);
                }
                $result = $this->getLocationDetails($id, $locationId, $sectors);
                break;
            case 'schedule':
                if (!($locationId = $params->fromQuery('locationId'))) {
                    return $this->handleError('getOrganisationInfo: missing location id');
                }
                if (!($startDate = $params->fromQuery('date'))) {
                    return $this->handleError('getOrganisationInfo: missing start date');
                }
                $result = $this->getSchedule($id, $locationId, $sectors, $startDate);
                break;
            case 'widget':
                if (!($locationId = $params->fromQuery('locationId') ?: null)) {
                    $locationId = $this->getLocationIdFromCookie($id);
                } else {
                    $this->setLocationIdCookie($id, $locationId);
                }
                $result = $this->getWidget(
                    $id,
                    $locationId,
                    $buildings,
                    $sectors,
                    (bool)$params->fromQuery('details', true),
                );
                break;
            case 'widget-location':
                if (!($locationId = $params->fromQuery('locationId') ?: null)) {
                    return $this->handleError('getOrganisationInfo: missing location id');
                }
                $this->setLocationIdCookie($id, $locationId);

                $result = $this->getWidgetLocationData(
                    $id,
                    $locationId,
                    $buildings,
                    $sectors,
                    (bool)$params->fromQuery('details', true),
                );
                break;
            case 'organisation-page-link':
                $result = $this->getOrganisationPageLink(
                    $id,
                    $sectors,
                    $params->fromQuery('parentName', null)
                );
                break;
            default:
                return $this->handleError('getOrganisationInfo: invalid element (' . ($element ?? '(none)') . ')');
        }

        return $this->formatResponse($result);
    }

    /**
     * Get any location id from cookie
     *
     * @param string $id Organisation id
     *
     * @return mixed
     */
    protected function getLocationIdFromCookie(string $id)
    {
        $cookie = $this->cookieManager->get(static::COOKIE_NAME);
        try {
            $data = json_decode($cookie, true, 512, JSON_THROW_ON_ERROR);
            return $data[$id]['loc'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set location id to a cookie
     *
     * @param string $id         Organisation id
     * @param string $locationId Location ID
     *
     * @return void
     */
    protected function setLocationIdCookie(string $id, string $locationId): void
    {
        $cookie = $this->cookieManager->get(static::COOKIE_NAME);
        try {
            $data = json_decode($cookie, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                $data = [];
            }
        } catch (\Exception $e) {
            // Bad cookie, rewrite:
            $data = [];
        }
        $data[$id] = [
            'loc' => $locationId,
            'ts' => time(),
        ];
        // Remember last five locations:
        while (count($data) > 5) {
            // Find oldest:
            $oldest = null;
            $oldestKey = null;
            foreach ($data as $key => $item) {
                if (null === $oldest || ($item['ts'] ?? null) < ($oldest['ts'] ?? null)) {
                    $oldest = $item;
                    $oldestKey = $key;
                }
            }
            unset($data[$oldestKey]);
        }
        // Update the cookie:
        $expire = time() + 365 * 60 * 60 * 24; // 1 year
        $this->cookieManager->set(static::COOKIE_NAME, json_encode($data), $expire);
    }

    /**
     * Get consortium info and location selection snippet
     *
     * @param string  $id             Organisation id
     * @param ?string $locationId     Selected location id, if any
     * @param array   $sectors        Sectors
     * @param array   $buildings      Buildings
     * @param bool    $consortiumInfo Whether to request information about all locations
     *
     * @return array
     */
    protected function getInfoAndLocationSelection(
        string $id,
        ?string $locationId,
        array $sectors,
        array $buildings,
        bool $consortiumInfo
    ): array {
        $orgInfo = $this->organisationInfo->getConsortiumInfo($sectors, $id, $buildings);

        $buildingFacetOperator = '';
        if ($orFacetSetting = $this->facetConfig['Results_Settings']['orFacets'] ?? null) {
            $orFacets = array_map('trim', explode(',', $orFacetSetting));
            if (
                !empty($orFacets[0])
                && ($orFacets[0] == '*' || in_array('building', $orFacets))
            ) {
                $buildingFacetOperator = '~';
            }
        }

        $consortiumInfo = $consortiumInfo ? $this->renderer->render(
            'organisationinfo/elements/consortium-info.phtml',
            compact('id', 'orgInfo', 'buildingFacetOperator', 'buildings')
        ) : '';
        $locationSelection = $this->renderer->render(
            'organisationinfo/elements/location-selection.phtml',
            compact('orgInfo')
        );
        $locationCount = count($orgInfo['list'] ?? []);
        $locationIdValid = false;
        $mapData = [];
        foreach ($orgInfo['list'] ?? [] as $org) {
            if ((string)$org['id'] === $locationId) {
                $locationIdValid = true;
            }
            if ($coordinates = $org['address']['coordinates'] ?? null) {
                if (($lat = $coordinates['lat'] ?? null) && ($lon = $coordinates['lon'] ?? null)) {
                    $mapData[$org['id']] = [
                        'id' => $org['id'],
                        'name' => $org['name'],
                        'openNow' => $org['openNow'],
                        'hasSchedules' => !empty($org['openTimes']['schedules']),
                        'lat' => $lat,
                        'lon' => $lon,
                        'address' => $org['address'],
                    ];
                }
            }
        }
        if (!$locationIdValid) {
            $locationId = null;
        }
        $defaultLocationId = $locationId
            ?? $orgInfo['consortium']['finna']['servicePoint']
            ?? $orgInfo['list'][0]['id']
            ?? null;
        return compact('consortiumInfo', 'locationSelection', 'locationCount', 'defaultLocationId', 'mapData');
    }

    /**
     * Get location details snippet
     *
     * @param string $id         Organisation id
     * @param string $locationId Location id
     * @param array  $sectors    Sectors
     *
     * @return array
     */
    protected function getLocationDetails(string $id, string $locationId, array $sectors): array
    {
        $orgInfo = $this->organisationInfo->getDetails($sectors, $id, $locationId);
        $found = !empty($orgInfo);
        if ($found) {
            $info = $this->renderer->render(
                'organisationinfo/elements/location-quick-info.phtml',
                compact('orgInfo')
            );
            $details = $this->renderer->render(
                'organisationinfo/elements/location-details.phtml',
                compact('orgInfo')
            );
        } else {
            $details = '';
            $info = '';
        }
        return compact('details', 'found', 'info');
    }

    /**
     * Get schedule snippet
     *
     * @param string $id         Organisation id
     * @param string $locationId Location id
     * @param array  $sectors    Sectors
     * @param string $startDate  Start date
     *
     * @return array
     */
    protected function getSchedule(
        string $id,
        string $locationId,
        array $sectors,
        string $startDate
    ): array {
        $orgInfo = $this->organisationInfo->getDetails($sectors, $id, $locationId, $startDate);

        $widget = $this->renderer->render(
            'organisationinfo/elements/location/schedule-week.phtml',
            compact('orgInfo')
        );
        $weekNum = date('W', strtotime($startDate));
        $currentWeek = date('W') === $weekNum;
        return compact('widget', 'weekNum', 'currentWeek');
    }

    /**
     * Get widget
     *
     * @param string  $id          Organisation id
     * @param ?string $locationId  Location id
     * @param array   $buildings   Buildings
     * @param array   $sectors     Sectors
     * @param bool    $showDetails Whether details are shown
     *
     * @return array
     */
    protected function getWidget(
        string $id,
        ?string $locationId,
        array $buildings,
        array $sectors,
        bool $showDetails
    ): array {
        $consortiumInfo = $this->organisationInfo->getConsortiumInfo($sectors, $id, $buildings);
        $defaultLocationId = $consortiumInfo['consortium']['finna']['servicePoint'] ?? null;
        if (null === $locationId) {
            $locationId = $defaultLocationId;
        }

        $orgInfo = $locationId ? $this->organisationInfo->getDetails($sectors, $id, $locationId) : [];
        if (!$orgInfo) {
            // Reset invalid location id and try with default one if possible:
            if ($locationId !== $defaultLocationId) {
                $locationId = $defaultLocationId;
                $orgInfo = $this->organisationInfo->getDetails($sectors, $id, $locationId);
            } else {
                $locationId = null;
            }
        }
        $orgInfo['list'] = $consortiumInfo['list'];

        $locationName = $this->getLocationName($locationId, $orgInfo);

        $widget = $this->renderer->render(
            'organisationinfo/elements/widget.phtml',
            compact('orgInfo', 'locationId', 'locationName', 'showDetails')
        );
        return compact('widget', 'locationId');
    }

    /**
     * Get widget data for a location
     *
     * @param string $id          Organisation id
     * @param string $locationId  Location id
     * @param array  $buildings   Buildings
     * @param array  $sectors     Sectors
     * @param bool   $showDetails Whether details are shown
     *
     * @return array
     */
    protected function getWidgetLocationData(
        string $id,
        string $locationId,
        array $buildings,
        array $sectors,
        bool $showDetails
    ): array {
        $consortiumInfo = $this->organisationInfo->getConsortiumInfo($sectors, $id, $buildings);
        $defaultLocationId = $consortiumInfo['consortium']['finna']['servicePoint'] ?? null;
        if (null === $locationId) {
            $locationId = $defaultLocationId;
        }

        $orgInfo = $this->organisationInfo->getDetails($sectors, $id, $locationId);
        if (!$orgInfo) {
            return [];
        }
        $orgInfo['list'] = $consortiumInfo['list'];

        $locationName = $this->getLocationName($locationId, $orgInfo);

        $openStatus = $this->renderer->render(
            'organisationinfo/elements/location/open-status.phtml',
            compact('orgInfo')
        );
        $schedule = $this->renderer->render(
            'organisationinfo/elements/location/schedule.phtml',
            compact('orgInfo')
        );
        $details = $showDetails
            ? $this->renderer->render(
                'organisationinfo/elements/location/widget-details.phtml',
                compact('orgInfo')
            ) : '';
        return compact('openStatus', 'schedule', 'details', 'locationId', 'locationName');
    }

    /**
     * Get organisation page image and link
     *
     * @param string $id         Organisation id
     * @param array  $sectors    Sectors
     * @param string $parentName Parent organisation name
     *
     * @return array
     */
    protected function getOrganisationPageLink(string $id, array $sectors, string $parentName): array
    {
        $orgInfo = $this->organisationInfo->lookup($sectors, $id);
        $found = !empty($orgInfo);
        $html = '';
        if ($found) {
            $html = $this->renderer->render(
                'organisationinfo/elements/organisation-page-link.phtml',
                compact('orgInfo', 'parentName', 'sectors')
            );
        }
        return compact('found', 'html');
    }

    /**
     * Get location name from organisation list
     *
     * @param ?string $locationId Location ID
     * @param array   $orgInfo    Organisation info
     *
     * @return string
     */
    protected function getLocationName(?string $locationId, array $orgInfo): string
    {
        if (null !== $locationId) {
            $locationId = (string)$locationId;
            foreach ($orgInfo['list'] ?? [] as $location) {
                if ((string)$location['id'] === $locationId) {
                    return $location['name'];
                }
            }
        }
        return '';
    }

    /**
     * Return an error response in JSON format and log the error message.
     *
     * @param string $outputMsg  Message to include in the JSON response.
     * @param string $logMsg     Message to output to the error log.
     * @param int    $httpStatus HTTPs status of the JSOn response.
     *
     * @return \Laminas\Http\Response
     */
    protected function handleError($outputMsg, $logMsg = '', $httpStatus = 400)
    {
        $this->logError(
            $outputMsg . ($logMsg ? " ({$logMsg})" : null)
        );

        return $this->formatResponse($outputMsg, $httpStatus);
    }
}
