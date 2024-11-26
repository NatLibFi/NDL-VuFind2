<?php

/**
 * Table Definition for user
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2020.
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
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Tuure Ilmarinen <tuure.ilmarinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Table;

use Laminas\Db\Sql\Select;

/**
 * Table Definition for user
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Tuure Ilmarinen <tuure.ilmarinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class User extends \VuFind\Db\Table\User
{
    /**
     * Retrieve a user object from the database based on email and institution prefix.
     *
     * @param string $email email to use for retrieval.
     *
     * @return UserRow
     */
    public function getByEmailAndInstitutionPrefix($email)
    {
        $row = $this->select(
            function (Select $select) use ($email) {
                $where = $select->where->equalTo('email', $email);
                // Allow retrieval by email only on users registered with database
                // method to keep e.g. Shibboleth accounts intact.
                $where->and->equalTo('auth_method', 'database');
                // Limit by institution code if set
                if ($prefix = $this->config->Site->institution ?? null) {
                    $prefix .= ':';
                    $where->and->like('username', "$prefix%");
                }
            }
        );
        return $row->current();
    }

    /**
     * Get users with due date reminders.
     *
     * @return array
     */
    public function getUsersWithDueDateReminders()
    {
        return $this->select(
            function (Select $select) {
                $subquery = new Select('user_card');
                $subquery->columns(['user_id']);
                $subquery->where->greaterThan('finna_due_date_reminder', 0);
                $select->where->in('id', $subquery);
                $select->order('username desc');
            }
        );
    }
}
