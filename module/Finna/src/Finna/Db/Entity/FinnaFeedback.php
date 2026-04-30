<?php

/**
 * Entity model for finna_feedback table.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * Entity model for finna_feedback table.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
#[ORM\Table(name: 'finna_feedback')]
#[ORM\Index(name: 'feedback_user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'feedback_created_idx', columns: ['created'])]
#[ORM\Index(name: 'feedback_status_idx', columns: ['status'], options: ['lengths' => [191]])]
#[ORM\Index(name: 'feedback_form_name_idx', columns: ['form_name'], options: ['lengths' => [191]])]
#[ORM\Index(name: 'feedback_updated_by_idx', columns: ['updated_by'])]
#[ORM\Entity]
class FinnaFeedback implements FinnaFeedbackEntityInterface
{
    /**
     * Unique ID.
     *
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected int $id;

    /**
     * User that created request.
     *
     * @var ?UserEntityInterface
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: UserEntityInterface::class)]
    protected ?UserEntityInterface $user = null;

    /**
     * Site URL.
     *
     * @var string
     */
    #[ORM\Column(name: 'ui_url', type: 'string', length: 255, nullable: false)]
    protected string $siteUrl;

    /**
     * Form name.
     *
     * @var string
     */
    #[ORM\Column(name: 'form', type: 'string', length: 255, nullable: false)]
    protected string $formName;

    /**
     * Form data.
     *
     * @var ?array
     */
    #[ORM\Column(name: 'message_json', type: 'json', nullable: true)]
    protected ?array $formData = null;

    /**
     * Message.
     *
     * @var string
     */
    #[ORM\Column(name: 'message', type: 'text', nullable: true)]
    protected string $message;

    /**
     * Creation date.
     *
     * @var DateTime
     */
    #[ORM\Column(name: 'created', type: 'datetime', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected DateTime $created;

    /**
     * Status.
     *
     * @var string
     */
    #[ORM\Column(name: 'status', type: 'string', length: 255, nullable: false, options: ['default' => 'open'])]
    protected string $status = 'open';

    /**
     * User that updated request.
     *
     * @var ?int
     */
    #[ORM\Column(name: 'modifier_id', type: 'integer', nullable: true)]
    protected ?int $modifierId = null;

    /**
     * Last modification date.
     *
     * @var ?DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: true)]
    protected ?DateTime $modified;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set the default value as a DateTime object
        $this->created = new DateTime();
    }

    /**
     * Get identifier (returns null for an uninitialized or non-persisted object).
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * Message setter.
     *
     * @param string $message Message
     *
     * @return static
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Message getter.
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
     * @param ?array $data Form data
     *
     * @return static
     */
    public function setFormData(?array $data): static
    {
        $this->formData = $data;
        return $this;
    }

    /**
     * Form data getter.
     *
     * @return ?array
     */
    public function getFormData(): ?array
    {
        return $this->formData;
    }

    /**
     * Form name setter.
     *
     * @param string $name Form name
     *
     * @return static
     */
    public function setFormName(string $name): static
    {
        $this->formName = $name;
        return $this;
    }

    /**
     * Form name getter.
     *
     * @return string
     */
    public function getFormName(): string
    {
        return $this->formName;
    }

    /**
     * Created setter.
     *
     * @param DateTime $dateTime Created date
     *
     * @return static
     */
    public function setCreated(DateTime $dateTime): static
    {
        $this->created = $dateTime;
        return $this;
    }

    /**
     * Created getter.
     *
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * Status setter.
     *
     * @param string $status Status
     *
     * @return static
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Status getter.
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
     * @return static
     */
    public function setSiteUrl(string $url): static
    {
        $this->siteUrl = $url;
        return $this;
    }

    /**
     * Site URL getter.
     *
     * @return string
     */
    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    /**
     * User setter.
     *
     * @param ?UserEntityInterface $user User that created request
     *
     * @return static
     */
    public function setUser(?UserEntityInterface $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * User getter.
     *
     * @return ?UserEntityInterface
     */
    public function getUser(): ?UserEntityInterface
    {
        return $this->user;
    }

    /**
     * Get modifier ID.
     *
     * @return ?int
     */
    public function getModifierId(): ?int
    {
        return $this->modifierId;
    }

    /**
     * Set modifier ID.
     *
     * @param ?int $modifierId Modifier ID
     *
     * @return static
     */
    public function setModifierId(?int $modifierId): static
    {
        $this->modifierId = $modifierId;

        return $this;
    }

    /**
     * Get modification date.
     *
     * @return ?DateTime
     */
    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    /**
     * Set modification date.
     *
     * @param ?DateTime $modified Modification date
     *
     * @return static
     */
    public function setModified(?DateTime $modified): static
    {
        $this->modified = $modified;
        return $this;
    }

    /**
     * Updated setter.
     *
     * Note: this updates the same field as setModificationDate
     *
     * @param DateTime $dateTime Last update date
     *
     * @return static
     */
    public function setUpdated(DateTime $dateTime): static
    {
        $this->modified = $dateTime;
        return $this;
    }

    /**
     * Updated getter.
     *
     * Note: this gets the date from the same field as getModificationDate, but returns current date if it's null
     *
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->modified ?? new DateTime();
    }

    /**
     * Updatedby setter.
     *
     * Not supported in finna_feedback!
     *
     * @param ?UserEntityInterface $user User that updated request
     *
     * @return static
     */
    public function setUpdatedBy(?UserEntityInterface $user): static
    {
        return $this;
    }

    /**
     * Updatedby getter.
     *
     * Not supported in finna_feedback!
     *
     * @return ?UserEntityInterface
     */
    public function getUpdatedBy(): ?UserEntityInterface
    {
        return null;
    }
}
