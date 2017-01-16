<?php
namespace Finna\Controller\Plugin;
use Zend\ServiceManager\ServiceManager;

class Factory
{
    /**
     * Construct the Recaptcha plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Recaptcha
     */
    public static function getRecaptcha(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        return new Recaptcha(
            $sm->getServiceLocator()->get('VuFind\Recaptcha'),
            $config
        );
    }
}