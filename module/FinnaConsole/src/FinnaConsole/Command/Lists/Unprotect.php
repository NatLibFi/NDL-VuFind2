<?php

/**
 * Console service for unprotecting lists.
 *
 * PHP version 7
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
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace FinnaConsole\Command\Lists;

use VuFind\Db\Row\RowGateway;

/**
 * Console service for unprotecting lists
 *
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Unprotect extends \FinnaConsole\Command\AbstractRecordUpdateCommand
{
    /**
     * Table display name
     *
     * @var string
     */
    protected $tableName = 'list';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Unprotect lists in the database';

    /**
     * Update a record
     *
     * @param RowGateway $record Record
     *
     * @return bool Whether changes were made
     */
    protected function changeRecord(RowGateway $record): bool
    {
        if ($record->finna_protected === 0) {
            return false;
        }
        $record->finna_protected = 0;
        // Fake a user to pass owner check:
        $user = new \StdClass();
        $user->id = $record->user_id;
        $record->save($user);
        return true;
    }
}
