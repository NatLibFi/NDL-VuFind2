<?php

namespace Finna\Controller;

use Interop\Container\ContainerInterface;

class FileControllerFactory
    implements \Laminas\ServiceManager\Factory\FactoryInterface
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
     * @throws ContainerException if any other error occurs
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        $result = new $requestedName(
            $container->get(\VuFind\Record\Loader::class),
            $container->get(\Finna\File\Loader::class),
            $container->get(\VuFind\Cache\Manager::class),
            $container->get(\VuFind\Session\Settings::class)
        );
        return $result;
    }
}