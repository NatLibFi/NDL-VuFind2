<?php

/**
 * Trait for logging from a test.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Tests
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace FinnaTest\Traits;

use function error_log;
use function file_put_contents;

/**
 * Trait for logging from a test.
 *
 * Stolen from VuFindTest\Integration\MinkTestCase, should perhaps be upstreamed
 *
 * @category VuFind
 * @package  Tests
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
trait LogTrait
{
    /**
     * Log a warning message
     *
     * @param string $consoleMsg Message to output to console
     * @param string $logMsg     Message to output to PHP error log
     *
     * @return void
     */
    protected function logWarning(string $consoleMsg, string $logMsg = ''): void
    {
        file_put_contents('php://stderr', PHP_EOL . $consoleMsg . PHP_EOL);
        if ($logMsg) {
            error_log($logMsg);
        }
    }
}
