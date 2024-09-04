<?php

/**
 * Interface for representing a transaction.
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
use Finna\Db\Type\FinnaTransactionStatus;
use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Interface for representing a transaction.
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
interface FinnaTransactionEntityInterface extends EntityInterface
{
    /**
     * Id getter
     *
     * @return ?int
     */
    public function getId(): ?int;

    /**
     * Transaction Identifier setter
     *
     * @param ?string $transactionId Transaction Identifier.
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setTransactionIdentifier(?string $transactionId): FinnaTransactionEntityInterface;

    /**
     * Transaction Identifier getter
     *
     * @return ?string
     */
    public function getTransactionIdentifier(): ?string;

    /**
     * Set user.
     *
     * @param UserEntityInterface $user User
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaTransactionEntityInterface;

    /**
     * Get user.
     *
     * @return UserEntityInterface
     */
    public function getUser(): UserEntityInterface;

    /**
     * Get user id
     *
     * @return ?int
     */
    public function getUserId(): ?int;

    /**
     * Source Id (driver) setter
     *
     * @param string $sourceId Source Id
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setSourceId(string $sourceId): FinnaTransactionEntityInterface;

    /**
     * Source Id (driver) getter
     *
     * @return string
     */
    public function getSourceId(): string;

    /**
     * Amount setter
     *
     * @param int $amount Amount
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setAmount(int $amount): FinnaTransactionEntityInterface;

    /**
     * Amount getter
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Currency setter
     *
     * @param string $currency Currency.
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setCurrency(string $currency): FinnaTransactionEntityInterface;

    /**
     * Currency getter
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Transaction fee setter
     *
     * @param int $amount Amount
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setTransactionFee(int $amount): FinnaTransactionEntityInterface;

    /**
     * Transaction fee getter
     *
     * @return int
     */
    public function getTransactionFee(): int;

    /**
     * Created setter
     *
     * @param DateTime $dateTime Created date
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setCreated(DateTime $dateTime): FinnaTransactionEntityInterface;

    /**
     * Created getter
     *
     * @return DateTime
     */
    public function getCreated(): Datetime;

    /**
     * Paid date setter
     *
     * @param ?DateTime $dateTime Paid date
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setPaidDate(?DateTime $dateTime): FinnaTransactionEntityInterface;

    /**
     * Paid date getter
     *
     * @return DateTime
     */
    public function getPaidDate(): ?Datetime;

    /**
     * Registration started setter
     *
     * @param ?DateTime $dateTime Registration start date
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setRegistrationStartDate(?DateTime $dateTime): FinnaTransactionEntityInterface;

    /**
     * Registration started getter
     *
     * @return ?DateTime
     */
    public function getRegistrationStartDate(): ?Datetime;

    /**
     * Registration date setter
     *
     * @param ?DateTime $dateTime Registration date
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setRegistrationDate(?DateTime $dateTime): FinnaTransactionEntityInterface;

    /**
     * Registration date getter
     *
     * @return ?DateTime
     */
    public function getRegistrationDate(): ?Datetime;

    /**
     * Status setter
     *
     * @param FinnaTransactionStatus $status Status
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setStatus(FinnaTransactionStatus $status): FinnaTransactionEntityInterface;

    /**
     * Status getter
     *
     * @return FinnaTransactionStatus
     */
    public function getStatus(): FinnaTransactionStatus;

    /**
     * Status message setter
     *
     * @param string $message Status message
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setStatusMessage(string $message): FinnaTransactionEntityInterface;

    /**
     * Status message getter
     *
     * @return string
     */
    public function getStatusMessage(): string;

    /**
     * Catalog username setter
     *
     * @param string $catUsername Catalog username
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setCatUsername(string $catUsername): FinnaTransactionEntityInterface;

    /**
     * Get catalog username.
     *
     * @return string
     */
    public function getCatUsername(): string;

    /**
     * Check if the transaction is in progress
     *
     * @return bool
     */
    public function isInProgress(): bool;

    /**
     * Check if the transaction is registered (fees marked paid in the ILS)
     *
     * @return bool
     */
    public function isRegistered(): bool;

    /**
     * Set transaction canceled
     *
     * @return void
     */
    public function setCanceled(): FinnaTransactionEntityInterface;

    /**
     * Check if the transaction is paid and needs registration with the ILS
     *
     * @return bool
     */
    public function needsRegistration(): bool;

    /**
     * Check if registration is in progress (i.e. started within 120 seconds)
     *
     * @return bool
     */
    public function isRegistrationInProgress(): bool;

    /**
     * Set transaction paid
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setPaid(): FinnaTransactionEntityInterface;

    /**
     * Set transaction registered
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setRegistered(): FinnaTransactionEntityInterface;

    /**
     * Set transaction status to "registration failed"
     *
     * @param string $msg Message
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setRegistrationFailed(string $msg): FinnaTransactionEntityInterface;

    /**
     * Set registration start timestamp
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setRegistrationStarted(): FinnaTransactionEntityInterface;

    /**
     * Set transaction reported date and status to "registration expired"
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setReportedAndExpired(): FinnaTransactionEntityInterface;

    /**
     * Set transaction status to "fines updated"
     *
     * @return FinnaTransactionEntityInterface
     */
    public function setFinesUpdated(): FinnaTransactionEntityInterface;
}
