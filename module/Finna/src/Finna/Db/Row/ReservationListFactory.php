<?php

namespace Finna\Db\Row;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;
use VuFind\Db\Row\RowGatewayFactory;

class ReservationListFactory extends RowGatewayFactory
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException&\Throwable if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory!');
        }
        $sessionManager = $container->get(\Laminas\Session\SessionManager::class);
        $session = new \Laminas\Session\Container('ReservationList', $sessionManager);
        return parent::__invoke(
            $container,
            $requestedName,
            [$session]
        );
    }
}
