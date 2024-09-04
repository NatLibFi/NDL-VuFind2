<?php

/**
 * Database service interface for Records.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use Closure;
use VuFind\Date\Converter as DateConverter;
use VuFind\Db\Service\RecordServiceInterface;
use VuFind\Record\Loader as RecordLoader;

/**
 * Database service interface for Records.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
interface FinnaRecordServiceInterface extends RecordServiceInterface
{
    /**
     * Check comment links
     *
     * @param Closure $getDedupRecordIds Callback to get dedup record IDs
     * @param Closure $msgCallback       Progress message callback
     *
     * @return void
     */
    public function checkCommentLinks(Closure $getDedupRecordIds, Closure $msgCallback): void;

    /**
     * Check rating links
     *
     * @param Closure $getDedupRecordIds Callback to get dedup record IDs
     * @param Closure $msgCallback       Progress message callback
     *
     * @return void
     */
    public function checkRatingLinks(Closure $getDedupRecordIds, Closure $msgCallback): void;

    /**
     * Check resources (records)
     *
     * @param RecordLoader $recordLoader Record loader
     * @param Closure      $msgCallback  Progress message callback
     *
     * @return void
     */
    public function checkResources(RecordLoader $recordLoader, Closure $msgCallback): void;

    /**
     * Verify resource metadata
     *
     * @param RecordLoader  $recordLoader  Record loader
     * @param DateConverter $dateConverter Date converter
     * @param ?string       $backendId     Optional backend ID to limit to
     * @param Closure       $msgCallback   Progress message callback
     *
     * @return void
     */
    public function verifyResourceMetadata(
        RecordLoader $recordLoader,
        DateConverter $dateConverter,
        ?string $backendId,
        Closure $msgCallback
    ): void;
}
