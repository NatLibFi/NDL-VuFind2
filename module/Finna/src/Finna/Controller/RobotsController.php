<?php
/**
 * Robots Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2021.
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
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Finna\Controller;

use Laminas\Config\Config;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Robots Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class RobotsController extends \VuFind\Controller\AbstractBase
{
    /**
     * VuFind configuration
     *
     * @var Config
     */
    protected $config;

    /**
     * Possible sitemap index file names
     *
     * @var array
     */
    protected $indexFileNames = [
        'sitemapIndex.xml.gz',
        'sitemapIndex.xml',
    ];

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm     Service manager
     * @param Config                  $config VuFind configuration
     */
    public function __construct(ServiceLocatorInterface $sm, Config $config)
    {
        // Call standard record controller initialization:
        parent::__construct($sm);

        $this->config = $config;
    }

    /**
     * Get the robots.txt file contents with modifications as necessary
     *
     * @return mixed
     */
    public function getAction()
    {
        $requestUri = $this->getRequest()->getUriString();
        $requestPath = substr($requestUri, 0, strrpos($requestUri, '/'));
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-type', 'text/plain; charset=UTF-8');
        if (!file_exists(ORIGINAL_WORKING_DIRECTORY . '/robots.txt')) {
            $response->setStatusCode(404);
            $response->setContent('404 Not Found');
            return $response;
        }
        $robots = file_get_contents(ORIGINAL_WORKING_DIRECTORY . '/robots.txt');

        foreach ($this->indexFileNames as $indexFileName) {
            if (file_exists(ORIGINAL_WORKING_DIRECTORY . '/' . $indexFileName)) {
                $parsed = $this->parseRobotsTxt($robots);
                $parsed['*'][] = "Sitemap: $requestPath/$indexFileName";
                $robots = $this->renderRobotsTxt($parsed);
                break;
            }
        }
        $response->setContent($robots);
        return $response;
    }

    /**
     * A naïve parser that returns robots.txt contents as an array by user-agent
     *
     * @param string $str robots.txt file contents
     *
     * @return array
     */
    protected function parseRobotsTxt(string $str): array
    {
        $lines = mb_split('\r\n|\n|\r', $str);
        $currentAgent = '*';
        $parsed = [];
        $empty = false;
        foreach ($lines as $line) {
            $line = trim($line);

            // Drop existing sitemap directives
            if (strncasecmp('sitemap:', $line, 8) === 0) {
                continue;
            }

            // Avoid consecutive empty lines:
            if ('' === $line) {
                if ($empty) {
                    continue;
                }
                $empty = true;
            } else {
                $empty = false;
            }

            if (preg_match('/^user-agent:\s*(.*?)$/i', $line, $matches)) {
                $currentAgent = $matches[1];
                continue;
            }
            $parsed[$currentAgent][] = $line;
        }
        return $parsed;
    }

    /**
     * Render a parsed robots.txt array as string
     *
     * @param array $parsed A parsed robots.txt array
     *
     * @return string
     */
    protected function renderRobotsTxt(array $parsed): string
    {
        $results = [];
        foreach ($parsed as $userAgent => $lines) {
            $results[] = "User-agent: $userAgent";
            $results = array_merge($results, $lines);
        }
        $results[] = '';
        return implode("\n", $results);
    }
}
