<?php
/**
 * Feedback Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2019.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Controller;

use Finna\Form\Form;
use VuFind\Log\LoggerAwareTrait;

/**
 * Feedback Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class FeedbackController extends \VuFind\Controller\FeedbackController
    implements \Laminas\Log\LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * True if form was submitted successfully.
     *
     * @var bool
     */
    protected $submitOk = false;

    /**
     * Show response after form submit.
     *
     * @param View    $view     View
     * @param Form    $form     Form
     * @param boolean $success  Was email sent successfully?
     * @param string  $errorMsg Error message (optional)
     *
     * @return void
     */
    protected function showResponse($view, $form, $success, $errorMsg = null)
    {
        if ($success) {
            $this->submitOk = true;
        }
        parent::showResponse($view, $form, $success, $errorMsg);
    }

    /**
     * Handles rendering and submit of dynamic forms.
     * Form configurations are specified in FeedbackForms.yaml.
     *
     * @return mixed
     */
    public function formAction()
    {
        if ($this->formWasSubmitted('submit')) {
            $formId
                = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
            if (\Finna\Form\R2Form::isR2RegisterForm($formId)) {
                return $this->forwardTo('R2Feedback', 'Form', ['id' => $formId]);
            }
        }

        $view = parent::formAction();

        if ($this->params()->fromPost('forcingLogin', false)) {
            // Parent response is a forced login for a non-logged user. Return it.
            return $view;
        }

        // Set record driver (used by FeedbackRecord form)
        $data = $this->getRequest()->getQuery('data', []);

        if ($id = ($this->getRequest()->getPost(
            'record_id',
            $this->getRequest()->getQuery('record_id')
        ))
        ) {
            [$source, $recId] = explode('|', $id, 2);
            $view->form->setRecord($this->getRecordLoader()->load($recId, $source));
            $data['record_id'] = $id;
        }
        $view->form->populateValues($data);

        if (!$this->submitOk) {
            return $view;
        }

        // Reset flashmessages set by VuFind
        $msg = $this->flashMessenger();
        $namespaces = ['error', 'info', 'success'];
        foreach ($namespaces as $ns) {
            $msg->setNamespace($ns);
            $msg->clearCurrentMessages();
        }

        $view->setTemplate('feedback/response');
        return $view;
    }

    /**
     * Legacy support for locally customized forms.
     *
     * @return void
     */
    public function emailAction()
    {
        $post = $this->getRequest()->getPost();
        $post->set('message', $post->get('comments'));

        return $this->forwardTo('Feedback', 'Form');
    }

    /**
     * Send submitted form data via email or save the data to the database.
     *
     * @param string $recipientName  Recipient name
     * @param string $recipientEmail Recipient email
     * @param string $senderName     Sender name
     * @param string $senderEmail    Sender email
     * @param string $replyToName    Reply-to name
     * @param string $replyToEmail   Reply-to email
     * @param string $emailSubject   Email subject
     * @param string $emailMessage   Email message
     *
     * @return array with elements success:boolean, errorMessage:string (optional)
     */
    protected function sendEmail(
        $recipientName,
        $recipientEmail,
        $senderName,
        $senderEmail,
        $replyToName,
        $replyToEmail,
        $emailSubject,
        $emailMessage
    ) {
        $formId = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
        if (!$formId) {
            $formId = 'FeedbackSite';
        }
        // Clone the form object to avoid messing with the existing instance:
        $form = clone $this->serviceLocator->get(\VuFind\Form\Form::class);
        $form->setFormId($formId);

        if ($formId === 'FeedbackRecord') {
            // Resolve recipient email from datasource configuration
            // when sending feedback on a record
            if ($id = ($this->getRequest()->getPost(
                'record_id',
                $this->getRequest()->getQuery('record_id')
            ))
            ) {
                [$source, $recId] = explode('|', $id, 2);
                $driver = $this->getRecordLoader()->load($recId, $source);
                $dataSource = $driver->getDataSource();
                $dataSources = $this->serviceLocator
                    ->get(\VuFind\Config\PluginManager::class)->get('datasources');
                $inst = $dataSources->$dataSource ?? null;
                $recipientEmail = $inst->feedbackEmail ?? null;
                if ($recipientEmail == null) {
                    throw new \Exception(
                        'Error sending record feedback:'
                        . 'Recipient Email Unset (see datasources.ini)'
                    );
                }
            }
        }

        if ($form->getSendMethod() === Form::HANDLER_EMAIL) {
            return parent::sendEmail(...func_get_args());
        } elseif ($form->getSendMethod() === Form::HANDLER_DATABASE) {
            $this->saveToDatabase(
                $form,
                $emailSubject,
                $emailMessage,
                $formId
            );
            return [true, null];
        } elseif ($form->getSendMethod() === Form::HANDLER_API) {
            return $this->sendToApi(
                $form,
                $emailSubject,
                $emailMessage,
                $formId
            );
        } else {
            throw new \Exception('Invalid form handler ' . $form->getSendMethod());
        }
    }

    /**
     * Save the feedback message to the database
     *
     * @param Form   $form    Form
     * @param string $subject Email subject
     * @param string $message Email message
     *
     * @return void
     */
    protected function saveToDatabase(
        Form $form,
        string $subject,
        string $message
    ): void {
        $user = $this->getUser();
        $userId = $user ? $user->id : null;

        $url = rtrim($this->getServerUrl('home'), '/');
        $url = substr($url, strpos($url, '://') + 3);

        $formFields = $form->getFormFields();

        $save = [];
        $params = (array)$this->params()->fromPost();
        foreach ($params as $key => $val) {
            if (!in_array($key, $formFields)) {
                continue;
            }
            $save[$key] = $val;
        }
        $save['emailSubject'] = $subject;
        $messageJson = json_encode($save);

        $message = $subject . PHP_EOL . '-----' . PHP_EOL . PHP_EOL . $message;

        $feedback = $this->getTable('Feedback');
        $feedback->saveFeedback(
            $url,
            $form->getFormId(),
            $userId,
            $message,
            $messageJson
        );
    }

    /**
     * Send the feedback message to an external API as a JSON message
     *
     * @param Form   $form    Form
     * @param string $subject Email subject
     *
     * @return array with elements success:boolean, errorMessage:string (optional)
     */
    protected function sendToApi(Form $form, string $subject): array
    {
        $user = $this->getUser();
        $userId = $user ? $user->id : null;

        $url = rtrim($this->getServerUrl('home'), '/');
        $url = substr($url, strpos($url, '://') + 3);

        $formFields = $form->getFormFields();

        $message = [];
        $params = (array)$this->params()->fromPost();
        foreach ($params as $key => $val) {
            if (!in_array($key, $formFields)) {
                continue;
            }
            $message[$key] = $val;
        }
        $message['emailSubject'] = $subject;
        $message['internalUserId'] = $userId;
        $messageJson = json_encode($message);

        $apiSettings = $form->getApiSettings();

        $httpService = $this->serviceLocator->get(\VuFindHttp\HttpService::class);
        $client = $httpService->createClient(
            $apiSettings['url'],
            \Laminas\Http\Request::METHOD_POST
        );
        $client->setOptions(['useragent' => 'VuFind']);
        $client->setRawBody($messageJson);
        $headers = array_merge(
            [
                'Content-Type' => 'application/json',
                'Content-Length' => mb_strlen($messageJson, 'UTF-8')
            ],
            (array)($apiSettings['headers'] ?? [])
        );
        try {
            if ($username = $apiSettings['username'] ?? '') {
                $method = ($apiSettings['authMethod'] ?? '') === 'digest'
                    ? \Laminas\Http\Client::AUTH_DIGEST
                    : \Laminas\Http\Client::AUTH_BASIC;
                $client->setAuth(
                    $username,
                    $apiSettings['password'] ?? '',
                    $method
                );
            }
            $client->setHeaders($headers);
            $result = $client->send();
            if ($result->getStatusCode() >= 300) {
                $this->logError(
                    "Sending of feedback form to '{$apiSettings['url']}' failed:"
                    . ' HTTP error ' . $result->getStatusCode() . ': '
                    . $result->getBody()
                );

                return [
                    false,
                    'An error has occurred'
                ];
            }
            if (!empty($apiSettings['successCodes'])) {
                $codeOk = in_array(
                    (string)$result->getStatusCode(),
                    $apiSettings['successCodes']
                );
                if (!$codeOk) {
                    $this->logError(
                        "Sending of feedback form to '{$apiSettings['url']}' failed:"
                        . ' HTTP status code ' . $result->getStatusCode()
                        . ' not in configured sucess codes'
                    );

                    return [
                        false,
                        'An error has occurred'
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logError(
                "Sending of feedback form to '{$apiSettings['url']}' failed: "
                . $e->getMessage()
            );

            return [
                false,
                'An error has occurred'
            ];
        }

        return [true, ''];
    }
}
