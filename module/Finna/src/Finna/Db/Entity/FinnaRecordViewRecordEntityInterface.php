<?php

/**
 * Interface for representing a Finna record view record.
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

use VuFind\Db\Entity\EntityInterface;

/**
 * Interface for representing a Finna record view record.
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
interface FinnaRecordViewRecordEntityInterface extends EntityInterface
{
    /**
     * Id getter
     *
     * @return int
     */
    public function getId(): ?int;

    /**
     * Backend setter
     *
     * @param string $backend Backend
     *
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function setBackend(string $backend): FinnaRecordViewRecordEntityInterface;

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
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function setSource(string $source): FinnaRecordViewRecordEntityInterface;

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
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function setRecordId(string $recordId): FinnaRecordViewRecordEntityInterface;

    /**
     * Record Id getter
     *
     * @return string
     */
    public function getRecordId(): string;

    /**
     * Format setter
     *
     * @param FinnaRecordViewRecordFormatEntityInterface $format Format
     *
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function setFormat(FinnaRecordViewRecordFormatEntityInterface $format): FinnaRecordViewRecordEntityInterface;

    /**
     * Format getter
     *
     * @return string
     */
    public function getFormat(): FinnaRecordViewRecordFormatEntityInterface;

    /**
     * Usage rights setter
     *
     * @param FinnaRecordViewRecordRightsEntityInterface $usageRights Usage rights
     *
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function setUsageRights(
        FinnaRecordViewRecordRightsEntityInterface $usageRights
    ): FinnaRecordViewRecordEntityInterface;

    /**
     * Usage rights getter
     *
     * @return string
     */
    public function getUsageRights(): FinnaRecordViewRecordRightsEntityInterface;

    /**
     * Online setter
     *
     * @param bool $online Online
     *
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function setOnline(bool $online): FinnaRecordViewRecordEntityInterface;

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
     * @return FinnaRecordViewRecordEntityInterface
     */
    public function setExtraMetadata(?string $extraMetadata): FinnaRecordViewRecordEntityInterface;

    /**
     * Extra metadata getter
     *
     * @return ?string
     */
    public function getExtraMetadata(): ?string;
}
