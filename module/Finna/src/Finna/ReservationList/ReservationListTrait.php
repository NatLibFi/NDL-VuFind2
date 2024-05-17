<?php


namespace Finna\ReservationList;

use Finna\ReservationList\ReservationListService;

trait ReservationListTrait
{
  protected $reservationListService;

  public function setReservationListService(ReservationListService $service): void
  {
    $this->reservationListService = $service;
  }

  public function getReservationLists(): array
  {
    return $this->reservationListService->getListsForUser($this);
  }

  public function getReservationListContainedIn(string $recordId, string $source): array
  {
    return $this->reservationListService->getListsContaining($this, $recordId, $source);
  }
}
