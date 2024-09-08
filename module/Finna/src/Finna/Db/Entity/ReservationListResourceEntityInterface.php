<?php

namespace Finna\Db\Entity;

use DateTime;
use VuFind\Db\Entity\EntityInterface;
use VuFind\Db\Entity\ResourceEntityInterface;

interface ReservationListResourceEntityInterface extends EntityInterface
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
     * @return ReservationListResourceEntityInterface
     */
    public function setResource(ResourceEntityInterface $resource): ReservationListResourceEntityInterface;

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
     * @param ReservationListEntityInterface $listId Id of list
     *
     * @return ReservationListResourceEntityInterface
     */
    public function setList(ReservationListEntityInterface $list): ReservationListResourceEntityInterface;

    /**
     * Created setter
     *
     * @param DateTime $dateTime Created date
     *
     * @return ReservationListResourceEntityInterface
     */
    public function setSaved(DateTime $dateTime): ReservationListResourceEntityInterface;

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
     * @return ReservationListResourceEntityInterface
     */
    public function setNotes(string $note): ReservationListResourceEntityInterface;

    /**
     * Data getter
     *
     * @return string
     */
    public function getNotes(): string;
}
