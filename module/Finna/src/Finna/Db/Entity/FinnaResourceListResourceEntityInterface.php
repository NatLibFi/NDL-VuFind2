<?php

namespace Finna\Db\Entity;

use DateTime;
use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;

interface FinnaResourceListResourceEntityInterface extends EntityInterface
{
    /**
     * Id getter
     *
     * @return ?int
     */
    public function getId(): ?int;

    /**
     * Resource ID setter
     *
     * @param ResourceEntityInterface $id Resource ID
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setResource(ResourceEntityInterface $resource): FinnaResourceListResourceEntityInterface;

    /**
     * Resource ID getter
     *
     * @return int
     */
    public function getResourceId(): int;

    /**
     * Get list id
     *
     * @return int
     */
    public function getListId(): int;

    /**
     * Set list id
     *
     * @param FinnaResourceListEntityInterface $listId Id of list
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setList(FinnaResourceListEntityInterface $list): FinnaResourceListResourceEntityInterface;

    /**
     * Created setter
     *
     * @param DateTime $dateTime Created date
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setSaved(DateTime $dateTime): FinnaResourceListResourceEntityInterface;

    /**
     * Created getter
     *
     * @return DateTime
     */
    public function getSaved(): Datetime;

    /**
     * Data setter
     *
     * @param string $data Data
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setNotes(string $note): FinnaResourceListResourceEntityInterface;

    /**
     * Data getter
     *
     * @return string
     */
    public function getNotes(): string;

    /**
     * Get user id
     *
     * @return int
     */
    public function getUserId(): int;

    /**
     * Set user
     *
     * @param UserEntityInterface $user User entity
     *
     * @return FinnaResourceListResourceEntityInterface
     */
    public function setUser(UserEntityInterface $user): FinnaResourceListResourceEntityInterface;
}
