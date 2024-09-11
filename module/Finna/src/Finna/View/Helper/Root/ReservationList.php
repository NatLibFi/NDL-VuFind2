<?php

namespace Finna\View\Helper\Root;

use Finna\ReservationList\ReservationListService;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\RecordDriver\DefaultRecord;

use function in_array;

class ReservationList extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Record driver
     *
     * @var DefaultRecord|null
     */
    protected ?DefaultRecord $driver = null;

    /**
     * Constructor
     *
     * @param array $config Reservation list yaml as an array
     */
    public function __construct(
        protected ReservationListService $reservationListService,
        protected array $config = []
    ) {
    }

    protected function getListsAvailableForRecord(DefaultRecord $driver): array
    {
        $recordOrganisation = $driver->getOrganisationInfoId();
        $result = [];
        if ($institutionConfig = $this->config[$recordOrganisation] ?? false) {
            $datasource = $driver->getDatasource();
            foreach ($institutionConfig['Lists'] ?? [] as $list) {
                if (in_array($datasource, $list['Datasources'] ?? []) && $list['Identifier'] ?? false) {
                    $result[] = $list['Identifier'];
                }
            }
        }
        return $result;
    }

    /**
     * Display buttons which routes the request to proper list procedures
     *
     * @return string
     */
    public function renderAddTemplate(DefaultRecord $driver): string
    {
        // Collect lists where we could potentially save this:
        $lists = $this->getListsAvailableForRecord($driver);
        // Set up the needed context in the view:
        $view = $this->getView();
        return $view->render('Helpers/FinnaResourceList-add.phtml');
    }

    public function getReservationListsForUser(UserEntityInterface|int $userOrId): array
    {
        return $this->reservationListService->getReservationListsForUser($userOrId);
    }
}
