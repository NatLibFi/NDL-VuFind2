<?php
/**
 * Statistics driver plugin interface
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\Statistics\Driver;

/**
 * Statistics driver plugin interface
 *
 * @category VuFind
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
interface DriverInterface
{
    /**
     * Add a new session to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View code (empty string for default view)
     * @param bool   $crawler     Whether the request comes from bot or crawler
     * @param array  $session     Session data
     *
     * @return void
     */
    public function addNewSession(
        string $institution,
        string $view,
        bool $crawler,
        array $session
    ): void;

    /**
     * Add a record view to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View code (empty string for default view)
     * @param bool   $crawler     Whether the request comes from bot or crawler
     * @param string $backend     Backend ID
     * @param string $source      Record source
     * @param string $recordId    Record ID
     * @param array  $formats     Record formats
     * @param array  $rights      Record usage rights
     *
     * @return void
     */
    public function addRecordView(
        string $institution,
        string $view,
        bool $crawler,
        string $backend,
        string $source,
        string $recordId,
        array $formats,
        array $rights
    ): void;

    /**
     * Add a page view to statistics
     *
     * @param string $institution Institution code
     * @param string $view        View code (empty string for default view)
     * @param bool   $crawler     Whether the request comes from bot or crawler
     * @param string $controller  Controller
     * @param string $action      Action
     *
     * @return void
     */
    public function addPageView(
        string $institution,
        string $view,
        bool $crawler,
        string $controller,
        string $action
    ): void;
}
