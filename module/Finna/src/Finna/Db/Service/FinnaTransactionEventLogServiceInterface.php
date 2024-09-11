<?php

/**
 * Database service interface for FinnaTransactionEventLog.
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
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaTransactionEntityInterface;
use Finna\Db\Entity\FinnaTransactionEventEntityInterface;
use VuFind\Db\Service\DbServiceInterface;

/**
 * Database service interface for FinnaTransactionEventLog.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
interface FinnaTransactionEventLogServiceInterface extends DbServiceInterface
{
    /**
     * Create a Finna transaction event entity object.
     *
     * @return FinnaTransactionEventEntityInterface
     */
    public function createEntity(): FinnaTransactionEventEntityInterface;

    /**
     * Add an event for a transaction
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction
     * @param string                          $status      Status message
     * @param array                           $data        Additional data
     *
     * @return void
     */
    public function addEvent(FinnaTransactionEntityInterface $transaction, string $status, array $data = []): void;
}
