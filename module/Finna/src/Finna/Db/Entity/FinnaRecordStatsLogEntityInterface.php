<?php

/**
 * Interface for representing a Finna record stats log entry.
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
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace Finna\Db\Entity;

/**
 * Interface for representing a Finna record stats log entry.
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
interface FinnaRecordStatsLogEntityInterface extends FinnaBaseStatsLogEntityInterface
{
    /**
     * Backend setter
     *
     * @param string $backend Backend
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function setBackend(string $backend): FinnaRecordStatsLogEntityInterface;

    /**
     * Backend getter
     *
     * @return string
     */
    public function getBackend(): string;

    /**
     * Source setter
     *
     * @param string $source Source
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function setSource(string $source): FinnaRecordStatsLogEntityInterface;

    /**
     * Source getter
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Record Id setter
     *
     * @param string $recordId Record Id
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function setRecordId(string $recordId): FinnaRecordStatsLogEntityInterface;

    /**
     * Record Id getter
     *
     * @return string
     */
    public function getRecordId(): string;

    /**
     * Formats setter
     *
     * @param string $formats Formats
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function setFormats(string $formats): FinnaRecordStatsLogEntityInterface;

    /**
     * Formats getter
     *
     * @return string
     */
    public function getFormats(): string;

    /**
     * Usage rights setter
     *
     * @param string $usageRights Usage rights
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function setUsageRights(string $usageRights): FinnaRecordStatsLogEntityInterface;

    /**
     * Usage rights getter
     *
     * @return string
     */
    public function getUsageRights(): string;

    /**
     * Online setter
     *
     * @param bool $online Online
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function setOnline(bool $online): FinnaRecordStatsLogEntityInterface;

    /**
     * Online getter
     *
     * @return string
     */
    public function getOnline(): bool;

    /**
     * Extra metadata setter
     *
     * @param ?string $extraMetadata Extra metadata
     *
     * @return FinnaRecordStatsLogEntityInterface
     */
    public function setExtraMetadata(?string $extraMetadata): FinnaRecordStatsLogEntityInterface;

    /**
     * Extra metadata getter
     *
     * @return ?string
     */
    public function getExtraMetadata(): ?string;
}
