<?php
/**
 * Feedback Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2018.
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
 * PHP version 7
 *
 * @category VuFind
 * @package  Controller
 * @author   Josiah Knoll <jk1135@ship.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Controller;

use Finna\Form\Form;
use Zend\Mail as Mail;

/**
 * Feedback Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Josiah Knoll <jk1135@ship.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class FeedbackController extends \VuFind\Controller\FeedbackController
{
    /**
     * Display Feedback home form.
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function homeAction()
    {
        if (!$this->dynamicFormsEnabled()) {
            return parent::homeAction();
        }

        return $this->forwardTo('Feedback', 'Form');
    }

    /**
     * Receives input from the user and sends an email to the recipient set in
     * the config.ini. This method is used only for legacy Feedback-form.
     *
     * @return void
     */
    public function emailAction()
    {
        $requestParams = $this->params();
        $view = $this->prepareView($requestParams);

        $feedbackConfig = $this->getFeedbackConfig();
        $user = $this->getUser();

        $view->category = $requestParams->fromPost(
            'category', $requestParams->fromQuery('category')
        );
        $view->name = $requestParams->fromPost(
            'name',
            $user ? trim($user->firstname . ' ' . $user->lastname) : ''
        );
        $view->users_email = $requestParams->fromPost(
            'email',
            $user ? $user->email : ''
        );
        $view->comments = $requestParams->fromPost(
            'comments', $requestParams->fromQuery('comments')
        );
        $view->url = $requestParams->fromPost(
            'url', $requestParams->fromQuery('url')
        );

        // Process form submission:
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {
            if (empty($view->comments)) {
                throw new \Exception('Missing data.');
            }
            $validator = new \Zend\Validator\EmailAddress();
            if (!empty($view->users_email)
                && !$validator->isValid($view->users_email)
            ) {
                throw new \Exception('Email address is invalid');
            }
            // These settings are set in the feedback section of your config.ini
            list($recipient_name, $recipient_email) = $this->getRecipient();

            $email_subject = isset($feedbackConfig->email_subject)
                ? $feedbackConfig->email_subject : 'VuFind Feedback';
            $email_subject .= ' (' . $this->translate($view->category) . ')';

            list($sender_name, $sender_email) = $this->getSender();

            if ($recipient_email == null) {
                throw new \Exception(
                    'Feedback Module Error: Recipient Email Unset (see config.ini)'
                );
            }

            $message = $this->translate('feedback_category') . ': '
                . $this->translate($view->category) . "\n";
            $message .= $this->translate('feedback_name') . ': '
                . ($view->name ? $view->name : '-') . "\n";
            $message .= $this->translate('feedback_email') . ': '
                . ($view->users_email ? $view->users_email : '-') . "\n";
            $message .= $this->translate('feedback_url') . ': '
                . ($view->url ? $view->url : '-') . "\n";

            $message .= $this->getUserStatus($user);

            $message .= "\n" . $this->translate('feedback_message') . ":\n";
            $message .= "----------\n\n{$view->comments}\n\n----------\n";

            $replyToName = $replyToEmail = null;
            if (!empty($view->users_email)) {
                $replyToName = $view->name;
                $replyToEmail = $view->users_email;
            }
            $this->sendEmail(
                $sender_name, $sender_email, $recipient_name, $recipient_email,
                $email_subject, $message, $replyToName, $replyToEmail
            );

            $view->setTemplate('feedback/response');
        }

        return $view;
    }

    /**
     * Handles rendering and submit of dynamic forms.
     * Form configurations are specified in FeedbackForms.json
     *
     * @return void
     */
    public function formAction()
    {
        if (!$this->dynamicFormsEnabled()) {
            return $this->redirect()->toRoute('feedback-home');
        }

        $formId = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
        if (!$formId) {
            $formId = 'FeedbackSite';
        }

        $translator = $this->serviceLocator->get('VuFind\Translator');
        $user = $this->getUser();

        $form = new Form($formId, $translator, $user);

        if (!$form->isEnabled()) {
            throw new \Exception('Form is disabled');
        }

        $view = $this->prepareView($this->params());
        $view->setTemplate('feedback/dynamic-form.phtml');
        $view->form = $form;
        $view->formId = $formId;
        $view->user = $user;

        if (!$this->formWasSubmitted('submit', $view->useRecaptcha)) {
            // Prefill name & email for logged users
            if ($user) {
                $form->setData(
                    [
                        '__name__' => $user->getDisplayName(),
                        '__email__' => $user['email']
                    ]
                );
            }
            return $view;
        }

        $params = $this->params();
        $form->setData($params->fromPost());

        if (! $form->isValid()) {
            return $view;
        }

        list($messageParams, $template) = $form->formatEmailMessage($this->params());
        $message = $this->getViewRenderer()->partial(
            $template, ['fields' => $messageParams]
        );
        $message .= (PHP_EOL . $this->getUserStatus($user));

        list($senderName, $senderEmail) = $this->getSender();
        $replyToName = $replyToEmail = null;
        if ($userEmail = ($params->fromPost('__email__', null))) {
            $replyToName = $params->fromPost('__name__', null);
            $replyToEmail = $userEmail;
        }
        $replyToName = $params->fromPost(
            '__name__',
            $user ? trim($user->firstname . ' ' . $user->lastname) : null
        );
        $replyToEmail = $params->fromPost(
            '__email__',
            $user ? $user->email : null
        );

        list($recipientName, $recipientEmail) = $this->getRecipient();

        $translated = [];
        foreach ($params->fromPost() as $key => $val) {
            $translated["%%{$key}%%"] = $translator->translate($val);
        }

        $subject = $this->translate($form->getEmailSubject(), $translated);

        $this->sendEmail(
            $senderName, $senderEmail, $recipientName, $recipientEmail,
            $subject, $message, $replyToName, $replyToEmail
        );

        $view->response = $form->getSubmitResponse();
        $view->setTemplate('feedback/response');

        return $view;
    }

    /**
     * Sends form data as an email.
     *
     * @param string $senderName     Sender name
     * @param string $senderEmail    Sender email address
     * @param string $recipientName  Recipient name
     * @param string $recipientEmail Recipient email address
     * @param string $subject        Email subject
     * @param string $message        Email message
     * @param string $replyToName    Reply to name (optional)
     * @param string $replyToEmail   Reply to email address (optional)
     *
     * @return void
     * @throws Exception
     */
    protected function sendEmail(
        $senderName, $senderEmail, $recipientName, $recipientEmail,
        $subject, $message, $replyToName = null, $replyToEmail = null
    ) {
        // This sets up the email to be sent
        $mail = new Mail\Message();
        $mail->setEncoding('UTF-8');
        $mail->setBody($message);
        $mail->setFrom($senderEmail, $senderName);
        if ($replyToEmail && $replyToName) {
            $mail->setReplyTo($replyToEmail, $replyToName);
        }

        $mail->addTo($recipientEmail, $recipientName);
        $mail->setSubject($subject);
        $headers = $mail->getHeaders();
        $headers->removeHeader('Content-Type');
        $headers->addHeaderLine('Content-Type', 'text/plain; charset=UTF-8');
        try {
            $this->serviceLocator->get('VuFind\Mailer')->getTransport()
                ->send($mail);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('feedback_error');
        }
    }

    /**
     * Prepare view.
     *
     * @param array $requestParams Request parameters.
     *
     * @return View
     */
    protected function prepareView($requestParams)
    {
        $user = $this->getUser();
        $view = $this->createViewModel();
        $view->useRecaptcha = $this->recaptcha()->active('feedback');

        $captcha = $requestParams->fromPost('captcha');

        // Support the old captcha mechanism for now
        if ($captcha == $this->translate('feedback_captcha_answer')) {
            $view->useRecaptcha = false;
        }
        $config = $this->getConfig();
        $institution = $config->Site->institution;
        $view->institutionName = $this->translate(
            "institution::$institution", null, $institution
        );

        // Try to handle cases like tritonia-tria
        if ($view->institutionName === $institution && strpos($institution, '-') > 0
        ) {
            $part = substr($institution, 0, strpos($institution, '-'));
            $view->institutionName = $this->translate(
                "institution::$part", null, $institution
            );
        }
        return $view;
    }

    /**
     * Format user info to be included in email message.
     *
     * @param array $user User
     *
     * @return string
     */
    protected function getUserStatus($user)
    {
        $msg = '';
        if ($user) {
            $loginMethod = $this->translate(
                'login_method_' . $user->auth_method,
                null,
                $user->auth_method
            );
            $msg .= $this->translate('feedback_user_login_method')
                . ": $loginMethod\n";
        } else {
            $msg .= $this->translate('feedback_user_anonymous') . "\n";
        }
        $permissionManager
            = $this->serviceLocator->get('VuFind\Role\PermissionManager');
        $roles = $permissionManager->getActivePermissions();
        $msg .= $this->translate('feedback_user_roles') . ': '
            . implode(', ', $roles) . "\n";

        return $msg;
    }

    /**
     * Return email sender from configuration.
     *
     * @return array with name, email
     */
    protected function getSender()
    {
        $config = $this->getFeedbackConfig();
        $email = isset($config->sender_email)
            ? $config->sender_email : 'noreply@vufind.org';
        $name = isset($config->sender_name)
            ? $config->sender_name : 'VuFind Feedback';

        return [$name, $email];
    }

    /**
     * Return email recipient from configuration.
     *
     * @return array with name, email
     */
    protected function getRecipient()
    {
        $feedbackConfig = $this->getFeedbackConfig();
        $config = $this->getConfig();

        $recipientEmail = !empty($feedbackConfig->recipient_email)
            ? $feedbackConfig->recipient_email : $config->Site->email;
        $recipientName = isset($feedbackConfig->recipient_name)
            ? $feedbackConfig->recipient_name : 'Your Library';

        return [$recipientName, $recipientEmail];
    }

    /**
     * Return feedback configuration.
     *
     * @return array
     */
    protected function getFeedbackConfig()
    {
        return $this->getConfig()->Feedback;
    }

    /**
     * Check if dynamic forms are enabled.
     *
     * @return boolean
     */
    protected function dynamicFormsEnabled()
    {
        $config = $this->getFeedbackConfig();
        return isset($config->dynamicForms) && $config->dynamicForms == true;
    }
}
