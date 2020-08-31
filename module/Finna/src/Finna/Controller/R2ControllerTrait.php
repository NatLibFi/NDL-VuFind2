<?php
/**
 * R2 controller trait.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace Finna\Controller;

use Laminas\Session\Container as SessionContainer;

/**
 * R2 controller trait.
 *
 * @category VuFind
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
trait R2ControllerTrait
{
    /**
     * Replace R2 new user registration form id with the id for returning
     * user registration form.
     *
     * @param string $formId Current form id
     *
     * @return null|string registration form id for returning user or null
     * if the form id was not modified.
     */
    protected function replaceR2RegisterFormId($formId)
    {
        if (! \Finna\Form\Form::isR2RegisterForm($formId, true)) {
            // Not a R2 registration form for new users
            return null;
        }

        // R2 registration form requested.
        // 1. If the user is not logged, default to new user form
        //    (this method gets called again after the login).
        // 2. For logged users, check if the user has been registered to REMS
        //    and replace form id with the id for returning users.
        if (!$user = $this->getUser()
        ) {
            return $formId;
        }

        $rems = $this->serviceLocator->get('Finna\RemsService\RemsService');
        try {
            $regId
                = \Finna\Form\Form::getR2RegisterFormId(!$rems->isUserRegistered());
        } catch (\Exception $e) {
            return null;
        }
        return $formId !== $regId ? $regId : null;
    }

    /**
     * Handles display and submit of R2 registration form.
     *
     * @return void
     */
    protected function processR2RegisterForm()
    {
        $formId = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
        if (!\Finna\Form\Form::isR2RegisterForm($formId)) {
            return null;
        }

        $inLightbox
            = $this->getRequest()->getQuery('layout', 'no') === 'lightbox'
               || 'layout/lightbox' == $this->layout()->getTemplate();

        $getRedirect = function () use ($inLightbox) {
            // Logged but not authorized (wrong login method etc), close form
            if ($inLightbox) {
                // Login completed inside lightbox: refresh page
                $response = $this->getResponse();
                $response->setStatusCode(205);
                return '';
            } else {
                return $this->redirect()->toRoute('search-home');
            }
        };

        if (!$user = $this->getUser()) {
            // Not logged, prompt login
            return $this->forceLogin();
        }

        // Verify that user is authenticated to access restricted R2 data.
        if (!$this->isAuthenticated()) {
            return $getRedirect();
        }

        // Check user permission from REMS and show registration if needed.
        $rems = $this->serviceLocator->get('Finna\RemsService\RemsService');
        try {
            if ($rems->isUserBlacklisted()) {
                return $getRedirect();
            }
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('R2_rems_connect_error');
            return $getRedirect();
        }

        try {
            if ($rems->hasUserAccess(true)) {
                // User already has access
                return $getRedirect();
            }
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('R2_rems_connect_error');
            return $getRedirect();
        }

        if ($this->formWasSubmitted('submit')) {
            // Handle submitted registration form
            $user = $this->getUser();

            $form = $this->serviceLocator->get('VuFind\Form\Form');
            $form->setFormId($formId);

            $view = $this->createViewModel(compact('form', 'formId', 'user'));
            $view->setTemplate('feedback/form');
            $params = $this->params()->fromPost();
            $form->setData($params);

            if (! $form->isValid()) {
                return $view;
            }

            $rems = $this->serviceLocator->get('Finna\RemsService\RemsService');

            // Collect submitted params required by REMS form
            $formParams = [];
            $formParams['usage_purpose']
                = (string)substr($params['usage_purpose'], -1);
            $formParams['usage_purpose_text'] = $params['usage_purpose'];
            if ($age = $params['age'] ?? null) {
                $formParams['age'] = '1';
            }
            if ($license = $params['license'] ?? null) {
                $formParams['license'] = '1';
            }

            // Take firstname and lastname from profile if available
            $firstname = !empty($user->firstname)
                ? $user->firstname : $params['firstname'];
            $lastname = !empty($user->lastname)
                ? $user->lastname : $params['lastname'];
            $email = $params['email'];

            try {
                $rems->registerUser(
                    $email,
                    $firstname,
                    $lastname,
                    $formParams
                );
            } catch (\Exception $e) {
                $this->flashMessenger()->addErrorMessage('R2_register_error');
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }

            return $getRedirect();
        }

        // User is authorized, let parent display the registration form
        return null;
    }

    /**
     * Return session for REMS data.
     *
     * @return SesionContainer
     */
    public function getR2Session()
    {
        return new SessionContainer(
            'R2Registration',
            $this->serviceLocator->get('VuFind\SessionManager')
        );
    }

    /**
     * Get the record loader
     *
     * @param bool $restricted Include restricted metadata?
     *
     * @return \VuFind\Record\Loader
     */
    public function getRecordLoader($restricted = false)
    {
        // By default, this returns a record loader that returns drivers without
        // restricted metadata.
        $loader = $this->serviceLocator->get(\VuFind\Record\Loader::class);
        $loader->setDefaultParams(['R2Restricted' => $restricted]);
        return $loader;
    }

    /**
     * Is the user authenticated to use R2?
     *
     * @return bool
     */
    protected function isAuthenticated()
    {
        $auth
            = $this->serviceLocator->get('LmcRbacMvc\Service\AuthorizationService');
        return $auth->isGranted('access.R2Authenticated');
    }
}
