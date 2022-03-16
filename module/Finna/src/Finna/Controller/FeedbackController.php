<?php
/**
 * Feedback Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2022.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class FeedbackController extends \VuFind\Controller\FeedbackController
    implements \Laminas\Log\LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        $form = $this->serviceLocator->get(\VuFind\Form\Form::class);
        $params = [];
        if ($refererHeader = $this->getRequest()->getHeader('Referer')) {
            $params['referrer'] = $refererHeader->getFieldValue();
        }
        if ($userAgentHeader = $this->getRequest()->getHeader('User-Agent')) {
            $params['userAgent'] = $userAgentHeader->getFieldValue();
        }
        $form->setFormId($formId, $params);

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
            // We may have modified the params, so state them explicitly:
            return parent::sendEmail(
                $recipientName,
                $recipientEmail,
                $senderName,
                $senderEmail,
                $replyToName,
                $replyToEmail,
                $emailSubject,
                $emailMessage
            );
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

        $save = $form->getContentsAsArray((array)$this->params()->fromPost());
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

        $recordParamMap = [
            'record' => 'record',
            'record_id' => 'recordId',
            'record_info' => 'recordInfo'
        ];

        $postParams = (array)$this->params()->fromPost();
        $message = $form->getContentsAsArray($postParams);
        foreach ($form->mapRequestParamsToFieldValues($postParams) as $field) {
            if (in_array($field['name'], array_keys($recordParamMap))) {
                continue;
            }
            $details = [
                'type' => $field['type'],
                'label' => $field['label'] ?? '',
                'labelTranslated' => $this->translate($field['label'] ?? ''),
                'value' => $field['value'] ?? '',
            ];
            if (isset($field['valueLabel'])) {
                $details['valueLabel'] = $field['valueLabel'];
                $details['valueLabelTranslated']
                    = $this->translate($field['valueLabel']);
            }
            $message['fields'][$field['name']] = $details;
        }
        foreach ($recordParamMap as $from => $to) {
            if (isset($message[$from])) {
                $message[$to] = $message[$from];
                unset($message[$from]);
            }
        }
        $message['emailSubject'] = $subject;
        $message['internalUserId'] = $userId;
        $message['viewBaseUrl'] = $url;
        if (!empty($message['recordId'])) {
            [$recordSource, $recordId] = explode('|', $message['recordId'], 2);
            $driver = $this->getRecordLoader()->load($recordId, $recordSource);
            $message['recordMetadata'] = [
                'title' => $driver->tryMethod('getTitle'),
                'authors' => $driver->tryMethod('getAuthorsWithRoles'),
                'publicationDates' => $driver->tryMethod('getPublicationDates'),
                'formats' => array_values(
                    array_unique(
                        array_map(
                            function ($s) {
                                if ($s instanceof \VuFind\I18n\TranslatableString) {
                                    return $s->getDisplayString();
                                }
                                return strval($s);
                            },
                            $driver->tryMethod('getFormats', [], [])
                        )
                    )
                ),
                'formatsRaw' => array_values(
                    array_unique(
                        array_map(
                            'strval',
                            $driver->tryMethod('getFormats', [], [])
                        )
                    )
                ),
                'isbns' => $driver->tryMethod('getISBNs'),
                'issns' => $driver->tryMethod('getISSNs'),
            ];
            if ($openUrl = $driver->tryMethod('getOpenUrl')) {
                parse_str($openUrl, $openUrlFields);
                $message['recordMetadata']['openurl'] = $openUrlFields;
            }
            if ($rawData = $driver->getRawData()) {
                if ($holdings = $rawData['holdings_txtP_mv'] ?? []) {
                    $message['recordHoldingsSummary'] = (array)$holdings;
                }
            }
        }

        $apiSettings = $form->getApiSettings();
        if ('test' === $apiSettings['url']) {
            if ($this->inLightbox()) {
                $this->flashMessenger()->addErrorMessage(
                    json_encode($message, JSON_PRETTY_PRINT)
                );
                return [
                    false,
                    'Simulated API request not sent'
                ];
            } else {
                header('Content-type: application/json');
                echo json_encode($message, JSON_PRETTY_PRINT);
                exit(0);
            }
        }
        $messageJson = json_encode($message);
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
