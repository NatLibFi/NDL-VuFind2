<?php

/**
 * Console service for protecting users.
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

namespace FinnaConsole\Command\Users;

use VuFind\Db\Row\RowGateway;

/**
 * Console service for protecting users
 *
 * @category VuFind
 * @package  Service
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Protect extends \FinnaConsole\Command\AbstractRecordUpdateCommand
{
    /**
     * Table display name
     *
     * @var string
     */
    protected $tableName = 'user';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Protect users in the database';

    /**
     * Update a record
     *
     * @param RowGateway $record Record
     *
     * @return bool Whether changes were made
     */
    protected function changeRecord(RowGateway $record): bool
    {
        if ($record->finna_protected === 1) {
            return false;
        }
        $record->finna_protected = 1;
        $record->save();
        return true;
    }
}
