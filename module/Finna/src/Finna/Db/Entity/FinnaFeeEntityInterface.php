<?php

/**
 * Interface for representing a transaction fee.
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

use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Interface for representing a transaction fee.
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
interface FinnaFeeEntityInterface extends EntityInterface
{
    /**
     * Id getter
     *
     * @return ?int
     */
    public function getId(): ?int;

    /**
     * Transaction setter
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction.
     *
     * @return FinnaFeeEntityInterface
     */
    public function setTransaction(FinnaTransactionEntityInterface $transaction): FinnaFeeEntityInterface;

    /**
     * Transaction getter
     *
     * @return FinnaTransactionEntityInterface
     */
    public function getTransaction(): FinnaTransactionEntityInterface;

    /**
     * Set user.
     *
     * @param UserEntityInterface $user User owning the list.
     *
     * @return FinnaFeeEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaFeeEntityInterface;

    /**
     * Get user.
     *
     * @return UserEntityInterface
     */
    public function getUser(): UserEntityInterface;

    /**
     * Title setter
     *
     * @param string $title Title
     *
     * @return FinnaFeeEntityInterface
     */
    public function setTitle(string $title): FinnaFeeEntityInterface;

    /**
     * Title getter
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Type setter
     *
     * @param string $type Type
     *
     * @return FinnaFeeEntityInterface
     */
    public function setType(string $type): FinnaFeeEntityInterface;

    /**
     * Type getter
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Description setter
     *
     * @param string $description Description
     *
     * @return FinnaFeeEntityInterface
     */
    public function setDescription(string $description): FinnaFeeEntityInterface;

    /**
     * Description getter
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Amount setter
     *
     * @param int $amount Amount
     *
     * @return FinnaFeeEntityInterface
     */
    public function setAmount(int $amount): FinnaFeeEntityInterface;

    /**
     * Amount getter
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Currency setter
     *
     * @param string $currency Currency
     *
     * @return FinnaFeeEntityInterface
     */
    public function setCurrency(string $currency): FinnaFeeEntityInterface;

    /**
     * Currency getter
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Fine Id setter
     *
     * @param string $fineId Fine ID (ILS)
     *
     * @return FinnaFeeEntityInterface
     */
    public function setFineId(string $fineId): FinnaFeeEntityInterface;

    /**
     * Fine Id getter
     *
     * @return string
     */
    public function getFineId(): string;

    /**
     * Organization setter
     *
     * @param string $organization Organization
     *
     * @return FinnaFeeEntityInterface
     */
    public function setOrganization(string $organization): FinnaFeeEntityInterface;

    /**
     * Organization getter
     *
     * @return string
     */
    public function getOrganization(): string;
}
