<?php

/**
 * Encapsulated record interface.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver\Feature;

use VuFindSearch\Response\RecordInterface;

/**
 * Encapsulated record interface.
 *
 * This interface should be implemented if an encapsulated record requires the
 * container record as context.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
interface EncapsulatedRecordInterface extends RecordInterface
{
    /**
     * Sets the container record.
     *
     * @param ContainerFormatInterface $containerRecord Container record.
     *
     * @return void
     */
    public function setContainerRecord(ContainerFormatInterface $containerRecord): void;

    /**
     * Returns the container record.
     *
     * @return ContainerFormatInterface
     */
    public function getContainerRecord(): ContainerFormatInterface;
}
