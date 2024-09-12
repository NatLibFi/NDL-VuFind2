<?php

/**
 * Row Definition for comments
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
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Row;

use DateTime;
use Finna\Db\Entity\FinnaCommentsEntityInterface;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\MissingField as MissingFieldException;

/**
 * Row Definition for comments
 *
 * @category VuFind
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property bool $finna_protected
 * @property string $finna_updated
 */
class Comments extends \VuFind\Db\Row\Comments implements FinnaCommentsEntityInterface
{
    public const NO_DATE = '2000-01-01 00:00:00';

    /**
     * Saves the properties to the database.
     *
     * This performs an intelligent insert/update, and reloads the
     * properties with fresh data from the table on success.
     *
     * @return mixed The primary key value(s), as an associative array if the
     *     key is compound, or a scalar if the key is single-column.
     * @throws ListPermissionException
     * @throws MissingFieldException
     */
    public function save()
    {
        $this->finna_updated = date('Y-m-d H:i:s');
        return parent::save();
    }

    /**
     * Protection status setter
     *
     * @param bool $protected Is the user protected
     *
     * @return static
     */
    public function setFinnaProtected(bool $protected): static
    {
        $this->finna_protected = $protected;
        return $this;
    }

    /**
     * Protection status getter
     *
     * @return bool
     */
    public function getFinnaProtected(): bool
    {
        return $this->finna_protected;
    }

    /**
     * Last update date setter
     *
     * @param ?DateTime $dateTime Last updated
     *
     * @return static
     */
    public function setFinnaUpdated(?DateTime $dateTime): static
    {
        $this->finna_updated = $dateTime ? $dateTime->format('Y-m-d H:i:s') : static::NO_DATE;
        return $this;
    }

    /**
     * Last update date getter
     *
     * @return DateTime
     */
    public function getFinnaUpdated(): ?Datetime
    {
        return $this->finna_updated !== static::NO_DATE
            ? DateTime::createFromFormat('Y-m-d H:i:s', $this->finna_updated) : null;
    }
}
