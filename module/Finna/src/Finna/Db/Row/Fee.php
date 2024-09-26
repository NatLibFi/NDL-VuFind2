<?php

/**
 * Row definition for online payment fee
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2024.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Row;

use Finna\Db\Entity\FinnaFeeEntityInterface;
use Finna\Db\Entity\FinnaTransactionEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Row definition for online payment fee
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property string $transaction_id
 * @property int $user_id
 * @property string $title
 * @property string $type
 * @property string $description
 * @property int $amount
 * @property string $currency
 * @property string $fine_id
 * @property string $organization
 */
class Fee extends \VuFind\Db\Row\RowGateway implements
    FinnaFeeEntityInterface,
    \VuFind\Db\Service\DbServiceAwareInterface
{
    use \VuFind\Db\Service\DbServiceAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'finna_fee', $adapter);
    }

    /**
     * Id getter
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * Transaction setter
     *
     * @param FinnaTransactionEntityInterface $transaction Transaction.
     *
     * @return FinnaFeeEntityInterface
     */
    public function setTransaction(FinnaTransactionEntityInterface $transaction): FinnaFeeEntityInterface
    {
        $this->transaction_id = $transaction->getId();
        return $this;
    }

    /**
     * Transaction getter
     *
     * @return FinnaTransactionEntityInterface
     */
    public function getTransaction(): FinnaTransactionEntityInterface
    {
        return $this->getDbService(\Finna\Db\Service\FinnaTransactionServiceInterface::class)
            ->getTransactionById($this->transaction_id);
    }

    /**
     * Set user.
     *
     * @param UserEntityInterface $user User owning the list.
     *
     * @return FinnaFeeEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaFeeEntityInterface
    {
        $this->user_id = $user->getId();
        return $this;
    }

    /**
     * Get user.
     *
     * @return UserEntityInterface
     */
    public function getUser(): UserEntityInterface
    {
        return $this->getDbService(\VuFind\Db\Service\UserServiceInterface::class)->getUserById($this->user_id);
    }

    /**
     * Title setter
     *
     * @param string $title Title
     *
     * @return FinnaFeeEntityInterface
     */
    public function setTitle(string $title): FinnaFeeEntityInterface
    {
        $this->title = mb_substr($title, 0, 255, 'UTF-8');
        return $this;
    }

    /**
     * Title getter
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Type setter
     *
     * @param string $type Type
     *
     * @return FinnaFeeEntityInterface
     */
    public function setType(string $type): FinnaFeeEntityInterface
    {
        $this->type = mb_substr($type, 0, 255, 'UTF-8');
        return $this;
    }

    /**
     * Type getter
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Description setter
     *
     * @param string $description Description
     *
     * @return FinnaFeeEntityInterface
     */
    public function setDescription(string $description): FinnaFeeEntityInterface
    {
        $this->description = mb_substr($description, 0, 255, 'UTF-8');
        return $this;
    }

    /**
     * Description getter
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Amount setter
     *
     * @param int $amount Amount
     *
     * @return FinnaFeeEntityInterface
     */
    public function setAmount(int $amount): FinnaFeeEntityInterface
    {
        $this->amount = (float)$amount;
        return $this;
    }

    /**
     * Amount getter
     *
     * @return int
     */
    public function getAmount(): int
    {
        return (int)$this->amount;
    }

    /**
     * Currency setter
     *
     * @param string $currency Currency
     *
     * @return FinnaFeeEntityInterface
     */
    public function setCurrency(string $currency): FinnaFeeEntityInterface
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Currency getter
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Fine Id setter
     *
     * @param string $fineId Fine ID (ILS)
     *
     * @return FinnaFeeEntityInterface
     */
    public function setFineId(string $fineId): FinnaFeeEntityInterface
    {
        $this->fine_id = $fineId;
        return $this;
    }

    /**
     * Fine Id getter
     *
     * @return string
     */
    public function getFineId(): string
    {
        return $this->fine_id ?? '';
    }

    /**
     * Organization setter
     *
     * @param string $organization Organization
     *
     * @return FinnaFeeEntityInterface
     */
    public function setOrganization(string $organization): FinnaFeeEntityInterface
    {
        $this->organization = mb_substr($organization, 0, 255, 'UTF-8');
        return $this;
    }

    /**
     * Organization getter
     *
     * @return string
     */
    public function getOrganization(): string
    {
        return $this->organization ?? '';
    }
}
