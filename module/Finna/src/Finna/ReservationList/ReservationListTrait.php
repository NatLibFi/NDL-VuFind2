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
}
