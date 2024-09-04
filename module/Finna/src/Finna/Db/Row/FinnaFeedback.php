<?php

/**
 * Row definition for feedback form data.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2019-2024.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Row;

use DateTime;
use Finna\Db\Entity\FinnaFeedbackEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Service\UserServiceInterface;

/**
 * Row definition for feedback form data.
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property int $id
 * @property int $user_id
 * @property string $ui_url
 * @property string $form
 * @property string $message_json
 * @property string $message
 * @property string $created
 * @property string $status
 * @property int $modifier_id
 * @property string $modification_date
 */
class FinnaFeedback extends \VuFind\Db\Row\RowGateway implements
    FinnaFeedbackEntityInterface,
    DbServiceAwareInterface
{
    use DbServiceAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'finna_feedback', $adapter);
    }

    /**
     * Id getter
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Message setter
     *
     * @param string $message Message
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setMessage(string $message): FinnaFeedbackEntityInterface
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Message getter
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Form data setter.
     *
     * @param array $data Form data
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setFormData(array $data): FinnaFeedbackEntityInterface
    {
        $this->message_json = json_encode($data);
        return $this;
    }

    /**
     * Form data getter
     *
     * @return array
     */
    public function getFormData(): array
    {
        return json_decode($this->message_json, true);
    }

    /**
     * Form name setter.
     *
     * @param string $name Form name
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setFormName(string $name): FinnaFeedbackEntityInterface
    {
        $this->form = $name;
        return $this;
    }

    /**
     * Form name getter
     *
     * @return string
     */
    public function getFormName(): string
    {
        return $this->form;
    }

    /**
     * Created setter.
     *
     * @param DateTime $dateTime Created date
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setCreated(DateTime $dateTime): FinnaFeedbackEntityInterface
    {
        $this->created = $dateTime->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * Created getter
     *
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $this->created);
    }

    /**
     * Updated setter.
     *
     * @param DateTime $dateTime Last update date
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setUpdated(DateTime $dateTime): FinnaFeedbackEntityInterface
    {
        $this->modification_date = $dateTime->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * Updated getter
     *
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $this->modification_date);
    }

    /**
     * Status setter.
     *
     * @param string $status Status
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setStatus(string $status): FinnaFeedbackEntityInterface
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Status getter
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Site URL setter.
     *
     * @param string $url Site URL
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setSiteUrl(string $url): FinnaFeedbackEntityInterface
    {
        $this->ui_url = $url;
        return $this;
    }

    /**
     * Site URL getter
     *
     * @return string
     */
    public function getSiteUrl(): string
    {
        return $this->ui_url;
    }

    /**
     * User setter.
     *
     * @param ?UserEntityInterface $user User that created request
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setUser(?UserEntityInterface $user): FinnaFeedbackEntityInterface
    {
        $this->user_id = $user?->getId();
        return $this;
    }

    /**
     * User getter
     *
     * @return ?UserEntityInterface
     */
    public function getUser(): ?UserEntityInterface
    {
        return $this->user_id
            ? $this->getDbServiceManager()->get(UserServiceInterface::class)->getUserById($this->user_id)
            : null;
    }

    /**
     * Updatedby setter.
     *
     * @param ?UserEntityInterface $user User that updated request
     *
     * @return FinnaFeedbackEntityInterface
     */
    public function setUpdatedBy(?UserEntityInterface $user): FinnaFeedbackEntityInterface
    {
        $this->modifier_id = $user ? $user->getId() : null;
        return $this;
    }

    /**
     * Updatedby getter
     *
     * @return ?UserEntityInterface
     */
    public function getUpdatedBy(): ?UserEntityInterface
    {
        return $this->modifier_id
            ? $this->getDbServiceManager()->get(UserServiceInterface::class)->getUserById($this->modifier_id)
            : null;
    }
}
