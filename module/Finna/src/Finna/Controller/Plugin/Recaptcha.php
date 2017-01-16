<?php
namespace Finna\Controller\Plugin;

class Recaptcha extends \VuFind\Controller\Plugin\Recaptcha
{
    protected $byPassCaptcha = [];

    public function __construct($r, $config)
    {
        parent::__construct($r, $config);
        if (!empty($config->Captcha->byPassCaptcha)) {
            $byPassCaptcha = $config->Captcha->byPassCaptcha->toArray();
            foreach ($byPassCaptcha as $domain => $authMethods) {
                $this->byPassCaptcha[$domain] = array_map(
                    'trim',
                    explode(',', $authMethods)
                );
            }
        }
    }

    public function active($domain = false)
    {
        if (!$domain || empty($this->byPassCaptcha[$domain])) {
            return parent::active($domain);
        }

        $authManager = $this->getController()
            ->getServiceLocator()
            ->get('VuFind\AuthManager');
        $user = $authManager->isLoggedIn();
        return $user
            ? !in_array($user->finna_auth_method, $this->byPassCaptcha[$domain])
            : parent::active($domain);
    }
}