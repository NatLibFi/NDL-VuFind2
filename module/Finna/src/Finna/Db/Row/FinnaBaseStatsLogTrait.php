<?php

/**
 * Trait to implement common setters/getters for stats entries
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
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Row;

use DateTime;
use Finna\Db\Type\FinnaStatisticsClientType;

/**
 * Trait to implement common setters/getters for stats entries
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
trait FinnaBaseStatsLogTrait
{
    /**
     * Institution setter
     *
     * @param string $institution Institution
     *
     * @return static
     */
    public function setInstitution(string $institution): static
    {
        $this->institution = mb_substr($institution, 0, 255, 'UTF-8');
        return $this;
    }

    /**
     * Institution getter
     *
     * @return string
     */
    public function getInstitution(): string
    {
        return $this->institution;
    }

    /**
     * View setter
     *
     * @param string $view View
     *
     * @return static
     */
    public function setView(string $view): static
    {
        $this->view = mb_substr($view, 0, 255, 'UTF-8');
        return $this;
    }

    /**
     * View getter
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Type setter
     *
     * @param FinnaStatisticsClientType $type Type
     *
     * @return static
     */
    public function setType(FinnaStatisticsClientType $type): static
    {
        $this->crawler = $type->value;
        return $this;
    }

    /**
     * Type getter
     *
     * @return FinnaStatisticsClientType
     */
    public function getType(): FinnaStatisticsClientType
    {
        return FinnaStatisticsClientType::from($this->crawler);
    }

    /**
     * Date setter
     *
     * @param DateTime $dateTime Date
     *
     * @return static
     */
    public function setDate(DateTime $dateTime): static
    {
        $this->date = $dateTime->format('Y-m-d');
        return $this;
    }

    /**
     * Date getter
     *
     * @return DateTime
     */
    public function getDate(): Datetime
    {
        return DateTime::createFromFormat('Y-m-d', $this->date);
    }

    /**
     * Count setter
     *
     * @param int $count Count
     *
     * @return static
     */
    public function setCount(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Count getter
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}
