<?php

/**
 * AJAX handler for getting organisation info.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2018.
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
use VuFind\Cookie\CookieManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Session\Settings as SessionSettings;

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
     * @var VuFind\CacheManager
     */
    protected $cacheManager;

    /**
     * Constructor
     *
     * @param SessionSettings     $ss               Session settings
     * @param CookieManager       $cookieManager    ILS connection
     * @param OrganisationInfo    $organisationInfo Organisation info
     * @param VuFind\CacheManager $cacheManager     Cache manager
     */
    public function __construct(
        SessionSettings $ss,
        CookieManager $cookieManager,
        OrganisationInfo $organisationInfo,
        $cacheManager
    ) {
        $this->sessionSettings = $ss;
        $this->cookieManager = $cookieManager;
        $this->organisationInfo = $organisationInfo;
        $this->cacheManager = $cacheManager;
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

        $parents = $params->fromPost('parent', $params->fromQuery('parent'));
        if (empty($parents)) {
            return $this->handleError('getOrganisationInfo: missing parent');
        }
        $reqParams = $params->fromPost('params', $params->fromQuery('params'));
        if (empty($reqParams['action'])) {
            return $this->handleError('getOrganisationInfo: missing action');
        }
        $cookieName = 'organisationInfoId';
        $cookie = $this->cookieManager->get($cookieName);
        $action = $reqParams['action'];

        $buildings = isset($reqParams['buildings'])
            ? explode(',', $reqParams['buildings']) : null;

        if ('details' === $action) {
            if (!isset($reqParams['id'])) {
                return $this->handleError('getOrganisationInfo: missing id');
            }
            if (isset($reqParams['id'])) {
                $id = $reqParams['id'];
                $expire = time() + 365 * 60 * 60 * 24; // 1 year
                $this->cookieManager->set($cookieName, $id, $expire);
            }
        }

        if (!isset($reqParams['id']) && $cookie) {
            $reqParams['id'] = $cookie;
        }

        if ('lookup' === $action) {
            $reqParams['link'] = $params->fromPost(
                'link',
                $params->fromQuery('link', false)
            );
            $reqParams['parentName'] = $params->fromPost(
                'parentName',
                $params->fromQuery('parentName', null)
            );
        }
        $parents = isset($parents['id']) ? [$parents] : $parents;
        $result = $this->getOrganisationInfo(
            $parents,
            $buildings,
            $reqParams,
            $action
        );
        if (!empty($result['error'])) {
            return $this->handleError($result['error']);
        }
        return $this->formatResponse($result ?: false);
    }

    /**
     * Get information for the organisation.
     *
     * @param array  $organisations Array containing arrays for organisations
     *                              - id     Organisation id
     *                              - sector Array containing sectors
     * @param ?array $buildings     Buildings to use in query
     * @param array  $reqParams     Request params
     * @param string $action        Action type
     *                              - lookup     Get all the museums/libraries
     *                              for the organisation
     *                              - details    Get opening times and other details
     *                              - consortium Get consortium info
     *
     * @return array
     */
    protected function getOrganisationInfo(
        array $organisations,
        ?array $buildings,
        array $reqParams,
        string $action
    ): array {
        $result = [];
        $libraries = [];
        foreach ($organisations as $organisation) {
            $id = $organisation['id'];
            if (empty($organisation['sector'])) {
                $cache = $this->cacheManager->getCache('organisation-info');
                $cacheKey = 'sectors';
                $sectors = $cache->getItem($cacheKey);
                if (empty($sectors[$id])) {
                    $fetchResult = $this->getSectorsForOrganisation($id);
                    if (!empty($fetchResult)) {
                        // Check for all the sectors
                        $sectors[$id] = $fetchResult;
                        $cache->setItem($cacheKey, $sectors);
                    }
                }
                if (!empty($sectors[$id])) {
                    $organisation['sector'] = $sectors[$id];
                }
            }
            if (!is_array($organisation['sector'])) {
                $organisation['sector'] = [['value' => $organisation['sector']]];
            }
            foreach ($organisation['sector'] as $sector) {
                if (empty($sector['value'])) {
                    continue;
                }
                $type = strstr($sector['value'], 'mus') ? 'mus' : 'lib';
                if ($type === 'lib') {
                    $libraries[] = $id;
                } else {
                    $result = array_merge(
                        $result,
                        $this->getItemsForMuseums(
                            $organisation,
                            $buildings,
                            $reqParams,
                            $action
                        )
                    );
                }
            }
        }
        $result = array_merge(
            $result,
            $this->getItemsForLibraries(
                array_values(array_unique($libraries)),
                $buildings,
                $reqParams,
                $action
            )
        );
        return $result;
    }

    /**
     * Get items for museums with parent id.
     *
     * @param array  $organisation Array of data for organisation.
     *                             - id     Organisation id
     *                             - sector Array containing sectors
     * @param ?array $buildings    Buildings to use in query
     * @param array  $reqParams    Request params
     * @param string $action       Action type
     *                             - lookup     Get all the museums
     *                             for the organisation
     *                             - details    Get opening times and other details
     *                             - consortium Get consortium info
     *
     * @return array
     */
    protected function getItemsForMuseums(
        array $organisation,
        ?array $buildings,
        array $reqParams,
        string $action
    ): array {
        $result = [];
        $reqParams['orgType'] = 'museum';
        try {
            $response = $this->organisationInfo->query(
                $organisation['id'],
                $reqParams,
                $buildings,
                $action
            );
            if ($response) {
                if ('lookup' === $action) {
                    $result = array_merge($result, $response['items']);
                } else {
                    $result = array_merge($result, $response);
                }
            }
        } catch (\Exception $e) {
            $this->handleError(
                'getOrganisationInfo: error reading '
                . 'organisation info (parent '
                . print_r($organisation, true) . ')',
                $e->getMessage()
            );
        }
        return $result;
    }

    /**
     * Get items for libraries.
     *
     * @param array  $libraries Libraries to use for fetching data.
     * @param array  $buildings Buildings to use in query
     * @param array  $reqParams Request params
     * @param string $action    Action type
     *                          - lookup     Get all the libraries
     *                          for the organisation
     *                          - details    Get opening times and other details
     *                          - consortium Get consortium info
     *
     * @return array
     */
    protected function getItemsForLibraries(
        array $libraries,
        ?array $buildings,
        array $reqParams,
        string $action
    ): array {
        if (!$libraries) {
            return [];
        }
        $result = [];
        $libraries = implode(',', $libraries);
        $reqParams['orgType'] = 'library';
        $result = $this->organisationInfo->query(
            $libraries,
            $reqParams,
            $buildings,
            $action
        );
        if (!is_array($result)) {
            $result = [];
        }
        return $result['items'] ?? $result;
    }

    /**
     * Get sectors for organisation as an associative array.
     *
     * @param string $institutionId Id of institution to search for sectors
     *
     * @return array [[value => sector]...]
     */
    protected function getSectorsForOrganisation(string $institutionId): array
    {
        $result = [];
        $list = $this->organisationInfo->getSectorsForOrganisation($institutionId);
        foreach ($list as $sector) {
            $result[] = [
                'value' => $sector,
            ];
        }
        return $result;
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
