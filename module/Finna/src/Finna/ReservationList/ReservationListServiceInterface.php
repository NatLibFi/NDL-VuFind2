<?php

namespace Finna\ReservationList;

use \VuFind\Db\Service\DbServiceInterface;
use \VuFind\Db\Entity\UserEntityInterface;
use \Laminas\Config\Config;

interface ReservationListServiceInterface extends DbServiceInterface
{
  /**
   * Construct.
   *
   * @param Config        $reservationListConfig Reservation List ini as Config
   */
  public function __construct(
      protected Config $reservationListConfig
  );

  /**
   * Checks that if the user has authority over certain reservation list
   *
   * @param UserEntityInterface $user UserEntityInterface to check
   * @param int  $id   Id of the list
   *
   * @return bool
   */
  public function userHasAuthority($user, $id): bool;

  /**
   * Adds a reservation list for a user.
   *
   * @param UserEntityInterface   $user        UserEntityInterface for whom the reservation list is being added.
   * @param string $description Description of the reservation list.
   * @param string $title       Title of the reservation list.
   * @param string $datasource  Data source of the reservation list.
   * @param string $building    Building associated with the reservation list.
   *
   * @return int ID of the newly added reservation list.
   */
  public function addListForUser(
      UserEntityInterface $user,
      string $description,
      string $title,
      string $datasource,
      string $building
  ): int;

  /**
   * Retrieves reservation lists for a given user.
   * Lists can exist in database or in an api provided service in future.
   *
   * @param UserEntityInterface $user UserEntityInterface for whom to retrieve the reservation lists.
   *
   * @return array An array of reservation lists.
   */
  public function getListsForUser(UserEntityInterface $user): array;

  /**
   * Retrieves reservation lists for a specific datasource.
   * Lists can coexist in database or external api in future.
   *
   * @param UserEntityInterface   $user       UserEntityInterface for whom to retrieve the reservation lists.
   * @param string $datasource Datasource for which to retrieve the reservation lists.
   *
   * @return array An array of reservation lists for the specified datasource.
   */
  public function getListsForDatasource(UserEntityInterface $user, string $datasource): array;

  /**
   * Retrieves reservation list for a specific user.
   *
   * @param UserEntityInterface $user UserEntityInterface for whom to retrieve the reservation list.
   * @param int  $id   ID of the reservation list.
   *
   * @return array
   */
  public function getListForUser(UserEntityInterface $user, int $id): array;
  /**
   * Retrieves the lists containing a specific record for a given user and source.
   *
   * @param UserEntityInterface   $user     The user identifier.
   * @param string $recordId The ID of the record.
   * @param string $source   The source of the record.
   *
   * @return array  An array of lists containing the specified record.
   */
  public function getListsContaining(UserEntityInterface $user, string $recordId, string $source);

  /**
   * Retrieves reservation lists without a record.
   *
   * @param UserEntityInterface   $user       UserEntityInterface object.
   * @param string $recordId   ID of the record.
   * @param string $source     Source of the record.
   * @param string $datasource Datasource of the record.
   *
   * @return array An array of reservations without a record.
   */
  public function getListsWithoutRecord(UserEntityInterface $user, string $recordId, string $source, string $datasource): array;

  /**
   * Save a record into a reservation list.
   *
   * @param UserEntityInterface   $user     UserEntityInterface to save to
   * @param string $recordId Id of the record
   * @param string $listId   Id of the desired list
   * @param string $notes    Notes to be added for a reservationlist resource
   * @param string $source   Source of the search backend where the record is obtained from.
   *                         Default is 'solr'
   *
   * @return bool True
   */
  public function addRecordToList(
      UserEntityInterface $user,
      string $recordId,
      string $listId,
      string $notes = '',
      string $source = DEFAULT_SEARCH_BACKEND
  ): bool;

  /**
   * Set list ordered, returns bool if the setting was successful
   *
   * @param UserEntityInterface   $user        UserEntityInterface
   * @param string $list_id     Id of the list
   * @param string $pickup_date $pickup_date
   *
   * @return bool
   */
  public function setOrdered($user, $list_id, $pickup_date): bool;

  /**
   * Delete list from the user, returns bool if the removal was successful
   *
   * @param UserEntityInterface   $user    UserEntityInterface
   * @param string $list_id Id of the list
   *
   * @return bool
   */
  public function deleteList($user, $list_id);

  /**
   * Delete a group of items from a reservation list.
   *
   * @param array $ids    Array of IDs in source|id format.
   * @param int   $listId ID of list to delete from
   * @param UserEntityInterface  $user   Logged in user
   *
   * @return bool
   */
  public function deleteItems($ids, $listId, $user): bool;

  /**
   * Get records for a list
   *
   * @param UserEntityInterface $user   UserEntityInterface
   * @param int  $listId ID of the list
   *
   * @return array
   */
  public function getRecordsForList($user, $listId): array;

  /**
   * Get records for list as HTML
   *
   * @param UserEntityInterface $user    UserEntityInterface
   * @param int  $list_id ID of the list
   *
   * @return string
   */
  public function getRecordsForListHTML($user, $list_id): string;
}