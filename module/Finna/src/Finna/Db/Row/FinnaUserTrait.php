<?php

/**
 * Trait for user row functionality
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2016.
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
 * @link     https://vufind.org Main Site
 */

namespace Finna\Db\Row;

use DateTime;

/**
 * Fake database row to represent a user in privacy mode.
 *
 * @category VuFind
 * @package  Db_Row
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
trait FinnaUserTrait
{
    /**
     * Activate a library card for the given username
     *
     * @param int $id Library card ID
     *
     * @return void
     * @throws \VuFind\Exception\LibraryCard
     */
    public function activateLibraryCard($id)
    {
        if (!$this->libraryCardsEnabled()) {
            throw new \VuFind\Exception\LibraryCard('Library Cards Disabled');
        }
        $userCard = $this->getDbTable('UserCard');
        $row = $userCard->select(['id' => $id, 'user_id' => $this->id])->current();

        if (!empty($row)) {
            $this->cat_username = $row->cat_username;
            $this->cat_password = $row->cat_password;
            $this->cat_pass_enc = $row->cat_pass_enc;
            $this->home_library = $row->home_library;
            $this->finna_due_date_reminder = $row->finna_due_date_reminder;
            $this->save();
        }
    }

    /**
     * Get a display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        if ($this->firstname && $this->lastname) {
            return $this->firstname . ' ' . $this->lastname;
        }
        if ($this->firstname || $this->lastname) {
            return $this->firstname . $this->lastname;
        }
        if ($this->email) {
            return $this->email;
        }
        return $this->getUsername();
    }

    /**
     * Due date reminder setting setter
     *
     * @param int $remind New due date reminder setting.
     *
     * @return static
     */
    public function setFinnaDueDateReminder(int $remind): static
    {
        $this->finna_due_date_reminder = $remind;
        return $this;
    }

    /**
     * Due date reminder setting getter
     *
     * @return int
     */
    public function getFinnaDueDateReminder(): int
    {
        return $this->finna_due_date_reminder;
    }

    /**
     * Nickname setter
     *
     * @param ?string $nickname Nickname or null for none
     *
     * @return static
     */
    public function setFinnaNickname(?string $nickname): static
    {
        $this->finna_nickname = $nickname;
        return $this;
    }

    /**
     * Nickname getter
     *
     * @return ?string
     */
    public function getFinnaNickName(): ?string
    {
        return $this->finna_nickname;
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
        $this->finna_protected = $protected ? 1 : 0;
        return $this;
    }

    /**
     * Protection status getter
     *
     * @return bool
     */
    public function getFinnaProtected(): bool
    {
        return $this->finna_protected ? true : false;
    }

    /**
     * Last expiration reminder date setter
     *
     * @param ?DateTime $dateTime Expiration reminder date
     *
     * @return static
     */
    public function setFinnaLastExpirationReminderDate(?DateTime $dateTime): static
    {
        $this->finna_last_expiration_reminder = $dateTime ? $dateTime->format('Y-m-d H:i:s') : '2000-01-01 00:00:00';
        return $this;
    }

    /**
     * Last expiration reminder date getter
     *
     * @return DateTime
     */
    public function getFinnaLastExpirationReminderDate(): ?Datetime
    {
        return $this->finna_last_expiration_reminder !== '2000-01-01 00:00:00'
            ? DateTime::createFromFormat('Y-m-d H:i:s', $this->finna_last_expiration_reminder) : null;
    }
}
