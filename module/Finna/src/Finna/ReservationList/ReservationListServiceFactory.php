<?php

namespace Finna\ReservationList;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Finna\ReservationList\ReservationListService;

class ReservationListServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $sm, $name, array $options = null)
    {
        $tableManager = $sm->get(\VuFind\Db\Table\PluginManager::class);
        return new ReservationListService(
            $tableManager->get(\Finna\Db\Table\ReservationList::class),
            $tableManager->get('resource'),
            $sm->get(\VuFind\Record\Cache::class),
            $tableManager->get(\VuFind\Db\Table\UserResource::class)
        );
    }
}
