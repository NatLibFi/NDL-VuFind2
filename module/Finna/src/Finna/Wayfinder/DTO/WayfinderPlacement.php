<?php

/**
 * Wayfinder data object.
 *
 * PHP version 8
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
 * @category Wayfinder
 * @package  Wayfinder
 * @author   Inlead <support@inlead.dk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://inlead.dk
 */

namespace Finna\Wayfinder\DTO;

/**
 * Wayfinder data object class.
 *
 * @category Wayfinder
 * @package  Wayfinder
 * @author   Inlead <support@inlead.dk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://inlead.dk
 */
class WayfinderPlacement
{
    /**
     * Branch placement.
     *
     * @var string
     */
    protected string $branch = '';

    /**
     * Department placement.
     *
     * @var string|null
     */
    protected ?string $department = null;

    /**
     * Location placement.
     *
     * @var string|null
     */
    protected ?string $location = null;

    /**
     * Sub-location placement.
     *
     * @var string|null
     */
    protected ?string $subLocation = null;

    /**
     * Shelf-mark placement.
     *
     * @var string|null
     */
    protected ?string $shelfMark = null;

    /**
     * DK5 placement.
     *
     * @var string|null
     */
    protected ?string $dk5 = null;

    /**
     * Author placement.
     *
     * @var string|null
     */
    protected ?string $author = null;

    /**
     * Dewey placement.
     *
     * @var string|null
     */
    protected ?string $dewey = null;

    /**
     * Gets branch placement.
     *
     * @return string
     */
    public function getBranch(): string
    {
        return $this->branch;
    }

    /**
     * Sets branch placement.
     *
     * @param string $branch Branch placement.
     *
     * @return WayfinderPlacement
     */
    public function setBranch(string $branch): WayfinderPlacement
    {
        $this->branch = $branch;
        return $this;
    }

    /**
     * Gets department placement.
     *
     * @return string|null
     */
    public function getDepartment(): ?string
    {
        return $this->department;
    }

    /**
     * Sets department placement.
     *
     * @param string|null $department Department placement.
     *
     * @return WayfinderPlacement
     */
    public function setDepartment(?string $department): WayfinderPlacement
    {
        $this->department = $department;
        return $this;
    }

    /**
     * Gets location placement.
     *
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Sets location placement.
     *
     * @param string|null $location Location placement.
     *
     * @return WayfinderPlacement
     */
    public function setLocation(?string $location): WayfinderPlacement
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Sets sub-location placement.
     *
     * @return string|null
     */
    public function getSubLocation(): ?string
    {
        return $this->subLocation;
    }

    /**
     * Gets sub-location placement.
     *
     * @param string|null $subLocation Sub-location placement.
     *
     * @return WayfinderPlacement
     */
    public function setSubLocation(?string $subLocation): WayfinderPlacement
    {
        $this->subLocation = $subLocation;
        return $this;
    }

    /**
     * Gets shelfmark placement.
     *
     * @return string|null
     */
    public function getShelfMark(): ?string
    {
        return $this->shelfMark;
    }

    /**
     * Sets shelf-mark placement.
     *
     * @param string|null $shelfMark Shelf-mark placement.
     *
     * @return WayfinderPlacement
     */
    public function setShelfMark(?string $shelfMark): WayfinderPlacement
    {
        $this->shelfMark = $shelfMark;
        return $this;
    }

    /**
     * Gets dk5 placement.
     *
     * @return string|null
     */
    public function getDk5(): ?string
    {
        return $this->dk5;
    }

    /**
     * Sets dk5 placement.
     *
     * @param string|null $dk5 DK5 placement.
     *
     * @return WayfinderPlacement
     */
    public function setDk5(?string $dk5): WayfinderPlacement
    {
        $this->dk5 = $dk5;
        return $this;
    }

    /**
     * Gets author placement.
     *
     * @return string|null
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * Sets author placement.
     *
     * @param string|null $author Author placement.
     *
     * @return WayfinderPlacement
     */
    public function setAuthor(?string $author): WayfinderPlacement
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Gets dewey placement.
     *
     * @return string|null
     */
    public function getDewey(): ?string
    {
        return $this->dewey;
    }

    /**
     * Sets dewey placement.
     *
     * @param string|null $dewey Dewey value.
     *
     * @return WayfinderPlacement
     */
    public function setDewey(?string $dewey): WayfinderPlacement
    {
        $this->dewey = $dewey;
        return $this;
    }

    /**
     * Gets dto array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'branch' => $this->branch,
            'department' => $this->department,
            'location' => $this->location,
            'sublocation' => $this->subLocation,
            'shelfmark' => $this->shelfMark,
            'dk5' => $this->dk5,
            'author' => $this->author,
            'dewey' => $this->dewey,
        ];
    }
}
