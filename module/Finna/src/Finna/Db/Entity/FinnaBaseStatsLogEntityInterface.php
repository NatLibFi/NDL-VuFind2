<?php

/**
 * Interface for representing a base Finna stats log entry.
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

use DateTime;
use Finna\Db\Type\FinnaStatisticsClientType;
use VuFind\Db\Entity\EntityInterface;

/**
 * Interface for representing a base Finna record stats log entry.
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
interface FinnaBaseStatsLogEntityInterface extends EntityInterface
{
    /**
     * Institution setter
     *
     * @param string $institution Institution
     *
     * @return static
     */
    public function setInstitution(string $institution): static;

    /**
     * Institution getter
     *
     * @return string
     */
    public function getInstitution(): string;

    /**
     * View setter
     *
     * @param string $view View
     *
     * @return static
     */
    public function setView(string $view): static;

    /**
     * View getter
     *
     * @return string
     */
    public function getView(): string;

    /**
     * Type setter
     *
     * @param FinnaStatisticsClientType $type Type
     *
     * @return static
     */
    public function setType(FinnaStatisticsClientType $type): static;

    /**
     * Type getter
     *
     * @return FinnaStatisticsClientType
     */
    public function getType(): FinnaStatisticsClientType;

    /**
     * Date setter
     *
     * @param DateTime $dateTime Date
     *
     * @return static
     */
    public function setDate(DateTime $dateTime): static;

    /**
     * Date getter
     *
     * @return DateTime
     */
    public function getDate(): Datetime;

    /**
     * Count setter
     *
     * @param int $count Count
     *
     * @return static
     */
    public function setCount(int $count): static;

    /**
     * Count getter
     *
     * @return int
     */
    public function getCount(): int;
}
