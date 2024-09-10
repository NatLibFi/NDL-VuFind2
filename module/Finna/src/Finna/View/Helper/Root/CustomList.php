<?php

namespace Finna\View\Helper\Root;

use VuFind\RecordDriver\DefaultRecord;

use function in_array;

class CustomList extends \Laminas\View\Helper\AbstractHelper
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
    public function __construct(protected array $config = [])
    {
    }

    public function __invoke(DefaultRecord $driver)
    {
        if (!$this->driver) {
            $this->driver = $driver;
        }
        return $this;
    }

    protected function getListsAvailableForRecord(): array
    {
        $recordOrganisation = $this->driver->getOrganisationInfoId();
        $result = [];
        if ($institutionConfig = $this->config[$recordOrganisation] ?? false) {
            $datasource = $this->driver->getDatasource();
            foreach ($institutionConfig['Lists'] ?? [] as $list) {
                if (in_array($datasource, $list['Datasources'] ?? []) && $list['Identifier'] ?? false) {
                    $result[] = $list['Identifier'];
                }
            }
        }
        var_dump($result);
        return $result;
    }

    /**
     * Display buttons which routes the request to proper list procedures
     *
     * @return string
     */
    public function renderAddTemplate(): string
    {
        // Collect lists where we could potentially save this:
        $lists = $this->getListsAvailableForRecord();
        // Set up the needed context in the view:
        $view = $this->getView();
        return $view->render('Helpers/FinnaResourceList-add.phtml');
    }
}
