<?php

/**
 * Wayfinder service integration.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022-2024.
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
 * @category Wayfinder
 * @package  Wayfinder
 * @author   Inlead <support@inlead.dk>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://inlead.dk
 */

namespace Finna\Wayfinder;

use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use VuFind\Log\LoggerAwareTrait;
use VuFindHttp\HttpServiceInterface;

/**
 * Wayfinder service.
 *
 * @category Wayfinder
 * @package  Wayfinder
 * @author   Inlead <support@inlead.dk>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://inlead.dk
 */
class WayfinderService
{
    use LoggerAwareTrait;

    /**
     * Whether service has valid config.
     *
     * @var bool
     */
    protected bool $isConfigured;

    /**
     * Constructor.
     *
     * @param ContainerInterface   $container   Service container.
     * @param array                $config      Configuration.
     * @param HttpServiceInterface $httpService HTTP service.
     * @param LoggerInterface      $logger      Logger service.
     */
    public function __construct(
        protected ContainerInterface $container,
        protected array $config,
        protected HttpServiceInterface $httpService,
        LoggerInterface $logger
    ) {
        $this->isConfigured = $this->isValidConfig();
        $this->logger = $logger;
    }

    /**
     * Gets wayfinder map link.
     *
     * @param array $payload Placement information array.
     *
     * @return string
     */
    public function getMarker(array $payload): string
    {
        return $this->fetchMarker($this->config['General']['url'], $payload);
    }

    /**
     * Whether service can be used, i.e. is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Fetches map link from wayfinder based on holding information.
     *
     * @param string $url       Wayfinder service url.
     * @param array  $placement Placement information.
     *
     * @return string
     */
    protected function fetchMarker(string $url, array $placement): string
    {
        if (!$this->isConfigured()) {
            $this->logWarning('Service not configured.');
            return '';
        }

        $response = $this->httpService->post(
            $url,
            json_encode(['placement' => $placement]),
            'application/json; charset=UTF-8'
        );

        if ($response->getStatusCode() !== Response::STATUS_CODE_200) {
            $this->logError(
                'Failed to read placement marker'
                . ' from url [' . $url . ']'
                . ' with args [' . var_export($placement, true) . '].'
                . ' Status code [' . $response->getStatusCode() . '].'
                . ' Response message [' . $response->getBody() . '].'
            );
            return '';
        }

        try {
            $decoded = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logError('Failed to parse Wayfinder response: ' . (string)$exception);
            return '';
        }

        if (empty($decoded['link'])) {
            $this->logError(
                'Failed to get marker link from response'
                . ' using [' . $url . '].'
                . ' Response [' . $response->getBody() . ']'
            );
            return '';
        }

        return $decoded['link'];
    }

    /**
     * Checks for valid config.
     *
     * @return bool
     */
    public function isValidConfig(): bool
    {
        if (empty($this->config)) {
            return false;
        }

        $enabled = filter_var($this->config['General']['enabled'] ?? false, FILTER_VALIDATE_BOOL);
        $urlValid = filter_var($this->config['General']['url'] ?? null, FILTER_VALIDATE_URL);

        return $enabled && $urlValid;
    }
}
