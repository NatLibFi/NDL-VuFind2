<?php

namespace Finna\ReservationList;

use Finna\Db\Table\ReservationList;
use VuFind\Db\Table\Resource as ResourceTable;
use VuFind\Db\Table\UserResource as UserResourceTable;
use VuFind\Exception\LoginRequired as LoginRequiredException;
use VuFind\Record\Cache as RecordCache;

class ReservationListService implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * UserResource table
     *
     * @var UserResourceTable
     */
    protected $userResourceTable;

    /**
     * Reservation list table
     *
     * @var ReservationList
     */
    protected $reservationList;

    /**
     * Resource database table
     *
     * @var ResourceTable
     */
    protected $resourceTable;

    /**
     * Record cache
     *
     * @var RecordCache
     */
    protected $recordCache = null;

    /**
     * Reservation list table
     *
     * @var ReservationListTable
     */

    public function __construct(
        ReservationList $reservationList,
        ResourceTable $resource,
        RecordCache $cache = null,
        UserResourceTable $userResourceTable
    ) {
        $this->recordCache = $cache;
        $this->reservationList = $reservationList;
        $this->resourceTable = $resource;
        $this->userResourceTable = $userResourceTable;
    }

    /**
     * Save this record to the user's favorites.
     *
     * @param array                 $params  Array with some or all of these keys:
     *  <ul>
     *    <li>mytags - Tag array to associate with record (optional)</li>
     *    <li>notes - Notes to associate with record (optional)</li>
     *    <li>list - ID of list to save record into (omit to create new list)</li>
     *  </ul>
     * @param \VuFind\Db\Row\User   $user    The user saving the record
     * @param array  RecordDriver[] $drivers Record drivers for record being saved
     *
     * @return array list information
     */
    public function saveMany(
        array $params,
        \VuFind\Db\Row\User $user,
        array $drivers
    ) {
        // Validate incoming parameters:
        if (!$user) {
            throw new LoginRequiredException('You must be logged in first');
        }
        $listId = $params['list'] ?? '';
        // Get or create a list object as needed:
        $list = $this->getListObject(
            $listId,
            $user
        );

        // check if list has custom order, if so add custom order keys for new items
        $index = $this->userResourceTable->getNextAvailableCustomOrderIndex($listId);

        // if target list is not in custom order then reverse
        if (! $this->userResourceTable->isCustomOrderAvailable($listId)) {
            $drivers = array_reverse($drivers);
        }

        // Get or create a resource object as needed:
        $resources = array_map(
            function ($driver) {
                $resource = $this->resourceTable->findResource(
                    $driver->getUniqueId(),
                    $driver->getSourceIdentifier(),
                    true,
                    $driver
                );
                // Persist record in the database for "offline" use
                $this->persistToCache($driver, $resource);
                return $resource;
            },
            $drivers
        );

        // Add the information to the user's account:
        $user->saveResources(
            $resources,
            $list,
            $params['mytags'] ?? [],
            $params['notes'] ?? '',
            true,
            $index
        );
        return ['listId' => $list->id];
    }

    public function getListsForUser(\VuFind\Db\Row\User $user)
    {
        $lists = $this->reservationList->select(['user_id' => $user->id]);
        $result = [];
        foreach ($lists as $list) {
            $result[] = [
                $list->id,
                $list->title
            ];
        }
        return $result;
    }

    public function deleteList($user, $id)
    {
        
    }
}
