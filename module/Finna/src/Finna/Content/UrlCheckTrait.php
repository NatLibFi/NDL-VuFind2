<?php
/**
 * Trait for checking external content url validity
 *
 * Dependencies:
 * - VuFind configuration available via getConfig method
 * - LoggerAwareTrait
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @package  Content
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/configuration:external_content Wiki
 */
namespace Finna\Content;

/**
 * Trait for checking external content url validity
 *
 * @category VuFind
 * @package  Content
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/configuration:external_content Wiki
 */
trait UrlCheckTrait
{
    /**
     * Check if the given URL is loadable according to configured rules
     *
     * @param string $url URL
     *
     * @return bool
     */
    protected function isUrlLoadable(string $url): bool
    {
        // Easy checks first
        if (empty($url)) {
            return false;
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return false;
        }

        $config = $this->getConfig();

        $allowedMode = $config->Record->allowed_external_hosts_mode ?? 'enforce';
        if ('disabled' === $allowedMode) {
            $allowedList = [];
        } else {
            $allowedList = isset($config->Record->allowed_external_hosts)
                ? $config->Record->allowed_external_hosts->toArray() : [];
        }
        $disallowedMode
            = $config->Record->disallowed_external_hosts_mode ?? 'enforce';
        if ('disabled' === $disallowedMode) {
            $disallowedList = [];
        } else {
            $disallowedList = isset($config->Record->disallowed_external_hosts)
                ? $config->Record->disallowed_external_hosts->toArray() : [];
        }

        // Return if nothing to check
        if (!$allowedList && !$disallowedList) {
            return true;
        }

        $host = mb_strtolower(parse_url($url, PHP_URL_HOST), 'UTF-8');

        $result = $this->checkHostAllowedByFilters(
            $url, $host, $allowedList, $disallowedList, $allowedMode, $disallowedMode
        );
        if (!$result) {
            return false;
        }

        // Check IPv4 addresses
        $ipv4 = $this->getIPv4Address($host);
        if ($ipv4 && $ipv4 !== $host) {
            $result = $this->checkHostAllowedByFilters(
                $url,
                $ipv4,
                $allowedList,
                $disallowedList,
                $allowedMode,
                $disallowedMode
            );
            if (!$result) {
                return false;
            }
        }
        // Check IPv6 addresses
        $ipv6 = $this->getIPv6Address($host);
        if ($ipv6 && $ipv6 !== $host) {
            $result = $this->checkHostAllowedByFilters(
                $url,
                $ipv6,
                $allowedList,
                $disallowedList,
                $allowedMode,
                $disallowedMode
            );
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the given host is allowed by the given filters
     *
     * @param string $url            Full URL
     * @param string $host           Host
     * @param array  $allowedList    List of allowed hosts
     * @param array  $disallowedList List of disallowed hosts
     * @param string $allowedMode    Allowed list handling mode
     * @param string $disallowedMode Disallowed list handling mode
     *
     * @return bool
     */
    protected function checkHostAllowedByFilters(string $url, string $host,
        array $allowedList, array $disallowedList, string $allowedMode,
        string $disallowedMode
    ): bool {
        // Check disallowed hosts first (probably a short list)
        if ($disallowedList && $this->checkHostFilterMatch($host, $disallowedList)) {
            if ('report' === $disallowedMode) {
                $this->logWarning("$url would be blocked in " . get_class($this));
            } elseif ('enforce-report' === $disallowedMode) {
                $this->logWarning("$url blocked in " . get_class($this));
            }
            if (in_array($disallowedMode, ['enforce', 'enforce-report'])) {
                return false;
            }
        }

        // Check allowed list
        if ($allowedList && !$this->checkHostFilterMatch($host, $allowedList)) {
            if ('report' === $allowedMode) {
                $this
                    ->logWarning("$url would not be allowed in " . get_class($this));
            } elseif ('enforce-report' === $allowedMode) {
                $this->logWarning("$url not allowed in " . get_class($this));
            }
            if (in_array($allowedMode, ['enforce', 'enforce-report'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the host name matches a filter
     *
     * @param string $host       Lower-cased host name
     * @param array  $filterList Filters
     *
     * @return bool
     */
    protected function checkHostFilterMatch(string $host, array $filterList): bool
    {
        foreach ($filterList as $filter) {
            if (strncmp('/', $filter, 1) === 0 && substr($filter, -1) === '/') {
                // Regular expression
                $match = preg_match($filter, $host);
            } else {
                $match = $filter === $host;
            }
            if ($match) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the IPv4 address for a host
     *
     * @param string $host Host
     *
     * @return string
     */
    protected function getIPv4Address(string $host): string
    {
        return gethostbyname($host);
    }

    /**
     * Get the IPv6 address for a host
     *
     * @param string $host Host
     *
     * @return string
     */
    protected function getIPv6Address(string $host): string
    {
        foreach (dns_get_record($host, DNS_AAAA) as $dnsRec) {
            $ipv6 = $dnsRec['ipv6'] ?? '';
            if ($ipv6) {
                return $ipv6;
            }
        }

        return '';
    }
}
