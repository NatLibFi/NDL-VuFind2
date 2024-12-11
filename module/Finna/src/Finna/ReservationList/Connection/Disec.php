<?php

/**
 * Disec connection handler
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\ReservationList\Connection;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\ReservationList\Form\Form;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Disec connection handler
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Disec extends AbstractBase
{
    /**
     * Disec orders url
     *
     * @param string
     */
    protected $ordersUrl;

    /**
     * API key used for disec authorization
     *
     * @param string
     */
    protected $apiKey;

    /**
     * Use catalog id to send user data instead of users first and last name
     *
     * @param bool
     */
    protected bool $useCatId = false;

    /**
     * Common headers in requests
     *
     * @var array
     */
    protected array $requestHeaders = [
        'Content-Type: application/json',
        'accept: */*',
    ];

    /**
     * Places an order
     *
     * @param Params              $params Params plugin
     * @param UserEntityInterface $user   User entity
     * @param Form                $form   Form posted when submitting the order
     *
     * @return array [external_id: Id in external service or null, success: true or false]
     */
    public function placeOrder(Params $params, UserEntityInterface $user, Form $form = null): array
    {
        $data = [];
        $client = $this->getService(\VuFindHttp\HttpService::class)->createClient($this->ordersUrl);
        $client->setHeaders($this->requestHeaders);
        $client->setMethod(\Laminas\Http\Request::METHOD_POST);

        $resources = [];
        $recordLoader = $this->getService(\VuFind\Record\Loader::class);
        foreach ($recordLoader->loadBatch($params->fromPost('resourceIDs', [])) as $record) {
            if ($identifiers = $record->tryMethod('getIdentifier', [])) {
                $resources[] = array_shift($identifiers);
            }
        }
        $data = [
            'resourceIds' => $resources,
            'contentInfo' => $params->fromPost('message', '') . PHP_EOL,
        ];
        $data['contentInfo'] .= $params->fromPost('pickup_date') . PHP_EOL;
        if ($catId = $user->getCatId()) {
            [, $id] = explode('.', $catId);
            if ($this->useCatId) {
                $data['kohaId'] = (int)$id;
            }
            $data['contentInfo'] .= 'cat_id: ' . $id;
        }
        if (empty($data['kohaId'])) {
            $data['customer'] = [
                'firstName' => $params->fromPost('firstName') ?? $user->getFirstname(),
                'lastName' => $params->fromPost('lastName') ?? $user->getLastname(),
                'email' => $params->fromPost('email') ?? $user->getEmail(),
            ];
        }
        $client->setRawBody(json_encode($data));
        $response = $client->send();

        if ($response->isSuccess()) {
            $body = json_decode($response->getBody(), true);
            return [
                'success' => true,
                'external_id' => $body['id'],
                'pickup_date' => $params->fromPost('pickup_date', ''),
            ];
        }
        $this->debug('Disec: failed to place order: ' . $response->getBody());
        return [
            'success' => false,
            'external_id' => null,
            'pickup_date' => null,
        ];
    }

    /**
     * Check list status. Used for external services.
     *
     * @param FinnaResourceListEntityInterface $list List to check for status
     * @param UserEntityInterface              $user Current logged in user
     *
     * @return string
     */
    public function getListStatus(FinnaResourceListEntityInterface $list, UserEntityInterface $user): string
    {
        $externalId = $list->getExternalId();
        $formedUrl = implode('/', [$this->ordersUrl, $externalId]);
        $client = $this->getService(\VuFindHttp\HttpService::class)->createClient($formedUrl);
        $client->setHeaders($this->requestHeaders);
        $client->setMethod(\Laminas\Http\Request::METHOD_GET);
        $response = $client->send();
        $status = ReservationListStatus::UNKNOWN;
        if ($response->isSuccess()) {
            $body = json_decode($response->getBody(), true);
            $status = ReservationListStatus::mapEnumFromString($body['status'] ?? '');
        } else {
            $this->debug('Disec: failed to fetch status for list: ' . $response->getBody());
        }
        return $status->getTranslationKey();
    }

    /**
     * Initialize connection handler
     *
     * @param array $config List specific configuration from ReservationList.yaml
     *
     * @return static
     * @throws \Exception If Disec connection is not configured properly
     */
    public function init(array $config): static
    {
        try {
            $baseUrl = $config['Connection']['base_url'];
            if (!str_ends_with($baseUrl, '/')) {
                $baseUrl .= '/';
            }
            $this->ordersUrl = $baseUrl . 'orders';
            $this->requestHeaders[] = 'X-API-Key: ' . $config['Connection']['secret'];
            $this->useCatId = $config['Connection']['use_cat_id'] ?? false;
        } catch (\Exception $e) {
            throw new \Exception('Disec: Invalid configuration');
        }
        return $this;
    }
}
