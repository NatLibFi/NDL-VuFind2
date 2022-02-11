<?php
/**
 * Configurable form.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2018-2021.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Form
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace Finna\Form;

use VuFind\Exception\BadConfig;

/**
 * Configurable form.
 *
 * @category VuFind
 * @package  Form
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class Form extends \VuFind\Form\Form
{
    /**
     * Email form handler
     *
     * @var string
     */
    public const HANDLER_EMAIL = 'email';

    /**
     * Database form handler
     *
     * @var string
     */
    public const HANDLER_DATABASE = 'database';

    /**
     * API form handler
     *
     * @var string
     */
    public const HANDLER_API = 'api';

    /**
     * Site feedback form id.
     *
     * @var string
     */
    public const FEEDBACK_FORM = 'FeedbackSite';

    /**
     * Record feedback form id.
     *
     * @var string
     */
    public const RECORD_FEEDBACK_FORM = 'FeedbackRecord';

    /**
     * Handlers that are considered safe for transmitting information about the user
     *
     * @var array
     */
    protected $secureHandlers = [
        Form::HANDLER_DATABASE,
        Form::HANDLER_API,
    ];

    /**
     * Form id
     *
     * @var string
     */
    protected $formId = '';

    /**
     * Institution name
     *
     * @var string
     */
    protected $institution = '';

    /**
     * Institution email
     *
     * @var string
     */
    protected $institutionEmail = '';

    /**
     * User
     *
     * @var User
     */
    protected $user = null;

    /**
     * ILS Patron
     *
     * @var array
     */
    protected $ilsPatron = null;

    /**
     * User roles
     *
     * @var array
     */
    protected $userRoles = [];

    /**
     * User library card barcode.
     *
     * @var string|null
     */
    protected $userCatUsername = null;

    /**
     * User patron id in library.
     *
     * @var string|null
     */
    protected $userCatId = null;

    /**
     * Record request forms that are allowed to send user's library card barcode
     * along with the form data.
     *
     * @var array Form ids
     */
    protected $recordRequestFormsWithBarcode = [];

    /**
     * View helper manager
     *
     * @var \Laminas\View\HelperPluginManager
     */
    protected $viewHelperManager = null;

    /**
     * Record driver
     *
     * @var \VuFind\RecordDriver\AbstractRecordDriver
     */
    protected $record = null;

    /**
     * Form settings (from YAML without parsing)
     *
     * @var array
     */
    protected $formSettings = [];

    /**
     * Get form id
     *
     * @return string
     */
    public function getFormId(): string
    {
        return $this->formId;
    }

    /**
     * Set form id
     *
     * @param string $formId Form id
     * @param array  $params Additional form parameters.
     *
     * @return void
     * @throws Exception
     */
    public function setFormId($formId, $params = [])
    {
        if (!$config = $this->getFormConfig($formId)) {
            throw new \VuFind\Exception\RecordMissing("Form '$formId' not found");
        }

        $this->formId = $formId;
        $this->formSettings = $config;
        parent::setFormId($formId, $params);
        $this->setName($formId);

        // Validate form settings
        if ($this->formSettings['includeBarcode'] ?? false) {
            $handler = $this->formSettings['sendMethod'] ?? Form::HANDLER_EMAIL;
            if (!in_array($handler, $this->secureHandlers)) {
                throw new \VuFind\Exception\BadConfig(
                    'Use one of the following values for sendMethod when'
                    . ' \'includeBarcode\' is enabled: '
                    . implode(', ', $this->secureHandlers)
                );
            }
            if ($this->user && ($catUsername = $this->user->cat_username)) {
                [, $barcode] = explode('.', $catUsername);
                $this->userCatUsername = $barcode;
            }
        }
        if ($this->formSettings['includePatronId'] ?? false) {
            if ($this->ilsPatron && ($catId = $this->ilsPatron['id'] ?? '')) {
                [, $id] = explode('.', $catId);
                $this->userCatId = $id;
            }
        }
        if ($this->getSendMethod() === Form::HANDLER_API) {
            if (empty($this->formSettings['apiSettings']['url'])) {
                throw new \VuFind\Exception\BadConfig(
                    "'apiSettings/url' is required when 'sendMethod' is '"
                    . Form::HANDLER_API . "'"
                );
            }
            if (strpos($this->formSettings['apiSettings']['url'], 'https://') !== 0
                && $this->formSettings['apiSettings']['url'] !== 'test'
                && 'development' !== APPLICATION_ENV
            ) {
                throw new \VuFind\Exception\BadConfig(
                    "'apiSettings/url' must begin with https://"
                );
            }
        }
    }

    /**
     * Set institution
     *
     * @param string $institution Institution
     *
     * @return void
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;
    }

    /**
     * Set institution email
     *
     * @param string $email Email
     *
     * @return void
     */
    public function setInstitutionEmail($email)
    {
        $this->institutionEmail = $email;
    }

    /**
     * Set user
     *
     * @param User  $user      User
     * @param array $roles     User roles
     * @param array $ilsPatron ILS patron account
     *
     * @return void
     */
    public function setUser($user, $roles, ?array $ilsPatron)
    {
        $this->user = $user;
        $this->userRoles = $roles;
        $this->ilsPatron = $ilsPatron;
    }

    /**
     * Set view helper manager
     *
     * @param \Laminas\View\HelperPluginManager $viewHelperManager manager
     *
     * @return void
     */
    public function setViewHelperManager($viewHelperManager)
    {
        $this->viewHelperManager = $viewHelperManager;
    }

    /**
     * Set record driver
     *
     * @param \VuFind\RecordDriver\AbstractRecordDriver $record Record
     *
     * @return void
     */
    public function setRecord($record)
    {
        $this->record = $record;
    }

    /**
     * Set form ids that are allowed to send user's library card barcode
     * along with the form data.
     *
     * @param array $formIds Form ids
     *
     * @return void
     */
    public function setRecordRequestFormsWithBarcode(array $formIds) : void
    {
        $this->recordRequestFormsWithBarcode = $formIds;
    }

    /**
     * Return form recipient.
     *
     * @param array $postParams Posted form data
     *
     * @return array with name, email or null if not configured
     */
    public function getRecipient($postParams = null)
    {
        if ($recipient = $this->getRecipientFromFormData($postParams)) {
            return [$recipient];
        }

        $recipients = parent::getRecipient();

        if ($this->getSendMethod() !== Form::HANDLER_EMAIL) {
            // Return a single "receiver" so that the response does not
            // get stored multiple times.
            return [$recipients[0]];
        }

        foreach ($recipients as &$recipient) {
            if (empty($recipient['email']) && $this->institutionEmail) {
                $recipient['email'] = $this->institutionEmail;
            }
        }

        return $recipients;
    }

    /**
     * Return form email message subject.
     *
     * @param array $postParams Posted form data
     *
     * @return string
     */
    public function getEmailSubject($postParams)
    {
        if (!$recipient = $this->getRecipientFromFormData($postParams)) {
            return parent::getEmailSubject($postParams);
        }

        // Replace posted recipient field value with label
        $recipientField = $this->getRecipientField($this->formElementConfig);
        $postParams[$recipientField] = $recipient['name'];

        return parent::getEmailSubject($postParams);
    }

    /**
     * Resolve email recipient based on posted form data.
     *
     * @param array $postParams Posted form data
     *
     * @return array with 'name' and 'email' keys or null if no form element
     * is configured to carry recipient address.
     */
    protected function getRecipientFromFormData($postParams)
    {
        if (!$recipientField = $this->getRecipientField($this->formElementConfig)) {
            return null;
        }

        $params = $this->mapRequestParamsToFieldValues($postParams);

        foreach ($params as $param) {
            if ($recipientField === $param['name']) {
                return [
                    'email' => $param['value'],
                    'name' => $this->translate($param['valueLabel'])
                ];
            }
        }

        return null;
    }

    /**
     * Return form help texts.
     *
     * @return array|null
     */
    public function getHelp()
    {
        $help = parent::getHelp();

        if (!$this->viewHelperManager) {
            throw new \Exception('ViewHelperManager not defined');
        }

        $escapeHtml = $this->viewHelperManager->get('escapeHtml');
        $transEsc = $this->viewHelperManager->get('transEsc');
        $translationEmpty = $this->viewHelperManager->get('translationEmpty');
        $organisationDisplayName
            = $this->viewHelperManager->get('organisationDisplayName');

        $preParagraphs = [];
        $postParagraphs = [];

        // 'feedback_instructions_html' translation
        if ($this->formId === self::FEEDBACK_FORM) {
            $key = 'feedback_instructions_html';
            $instructions = $this->translate($key);
            if ($instructions !== $key && !$translationEmpty($instructions)) {
                $preParagraphs[] = $instructions;
            }
        }

        // Help texts from configuration
        $pre = isset($this->formConfig['help']['pre'])
            && !$translationEmpty($this->formConfig['help']['pre'])
            ? $this->translate($this->formConfig['help']['pre'])
            : null;
        if ($pre) {
            $preParagraphs[] = $pre;
        }
        $post = isset($this->formConfig['help']['post'])
            && !$translationEmpty($this->formConfig['help']['post'])
            ? $this->translate($this->formConfig['help']['post'])
            : null;
        if ($post) {
            $postParagraphs[] = $post;
        }

        if ($this->formId === self::RECORD_FEEDBACK_FORM && null !== $this->record) {
            // Append receiver info after general record feedback instructions
            // (translation key for this is defined in FeedbackForms.yaml)
            if (!$translationEmpty('feedback_recipient_info_record')) {
                $preParagraphs[] = $transEsc(
                    'feedback_recipient_info_record',
                    [
                        '%%institution%%'
                            => $organisationDisplayName($this->record, true)
                    ]
                );
            }
            $datasourceKey = 'feedback_recipient_info_record_'
                . $this->record->getDataSource() . '_html';
            if (!$translationEmpty($datasourceKey)) {
                $preParagraphs[] = '<span class="datasource-info">'
                    . $this->translate($datasourceKey) . '</span>';
            }
        } elseif (!($this->formConfig['hideRecipientInfo'] ?? false)
            && $this->institution
        ) {
            // Receiver info
            $institution = $this->institution;
            $institutionName = $this->translate(
                "institution::$institution",
                null,
                $institution
            );

            // Try to handle cases like tritonia-tria
            if ($institutionName === $institution && strpos($institution, '-') > 0
            ) {
                $part = substr($institution, 0, strpos($institution, '-'));
                $institutionName = $this->translate(
                    "institution::$part",
                    null,
                    $institution
                );
            }

            $translationKey = $this->getSendMethod() === Form::HANDLER_EMAIL
                ? 'feedback_recipient_info_email'
                : 'feedback_recipient_info';

            $recipientInfo = $this->translate(
                $translationKey,
                ['%%institution%%' => $institutionName]
            );

            $postParagraphs[] = '<strong>' . $recipientInfo . '</strong>';
        }

        // Append record title
        if (null !== $this->record
            && ($this->formId === self::RECORD_FEEDBACK_FORM
            || $this->isRecordRequestFormWithBarcode())
        ) {
            $preParagraphs[] = '<strong>'
                . $transEsc('feedback_material') . '</strong>:<br>'
                . $escapeHtml($this->record->getTitle());
        }

        if ($this->userCatUsername) {
            $preParagraphs[] = $this->translate(
                'feedback_library_card_barcode_html',
                ['%%barcode%%' => $escapeHtml($this->userCatUsername)]
            );
        }
        if ($this->userCatId) {
            $postParagraphs[] = $this->translate(
                'feedback_library_patron_id_html',
                ['%%id%%' => $escapeHtml($this->userCatId)]
            );
        }

        $pre = implode('</div><div>', $preParagraphs);
        $help['pre'] = $pre ? "<div>$pre</div>" : '';
        $post = implode('</div><div>', $postParagraphs);
        $help['post'] = $post ? "<div>$post</div>" : '';

        return $help;
    }

    /**
     * Map request parameters to field values
     *
     * @param array $requestParams Request parameters
     *
     * @return array
     */
    public function mapRequestParamsToFieldValues(array $requestParams): array
    {
        $params = parent::mapRequestParamsToFieldValues($requestParams);

        $params = array_filter(
            $params,
            function ($param) {
                return !empty($param['label']) || !empty($param['value']);
            }
        );
        reset($params);

        if ($this->userCatUsername) {
            // Append library card barcode
            $field = [
                'type' => 'text',
                'name' => 'userCatUsername',
                'label' => $this->translate('Library Catalog Username'),
                'value' => $this->userCatUsername
            ];
            if ($idx = array_search('email', array_column($params, 'type'))) {
                array_splice($params, $idx + 1, 0, [$field]);
            } else {
                $params[] = $field;
            }
        }

        if ($this->userCatId) {
            // Append patron's id in library
            $field = [
                'type' => 'text',
                'name' => 'userCatId',
                'label' => $this->translate('Unique patron identifier'),
                'value' => $this->userCatId
            ];
            if ($idx = array_search('email', array_column($params, 'type'))) {
                array_splice($params, $idx + 1, 0, [$field]);
            } else {
                $params[] = $field;
            }
        }

        if (!$this->isRecordRequestFormWithBarcode()) {
            // Append user logged status and permissions
            $loginMethod = $this->user ?
                $this->translate(
                    'login_method_' . $this->user->auth_method,
                    null,
                    $this->user->auth_method
                ) : $this->translate('feedback_user_anonymous');

            $label = $this->translate('feedback_user_login_method');
            $params[] = [
                'name' => 'userLoginMethod',
                'type' => 'text',
                'label' => $label,
                'value' => $loginMethod
            ];

            if ($this->user) {
                $label = $this->translate('feedback_user_roles');
                $params[] = [
                    'name' => 'userRoles',
                    'type' => 'text',
                    'label' => $label,
                    'value' => implode(', ', $this->userRoles)
                ];
            }
        }

        return $params;
    }

    /**
     * Get form contents as an array
     *
     * @param array $requestParams Request parameters
     *
     * @return array
     */
    public function getContentsAsArray(array $requestParams): array
    {
        $params = $this->mapRequestParamsToFieldValues($requestParams);
        $result = array_column($params, 'value', 'name');
        return $result;
    }

    /**
     * Return the handler to be used for sending the email
     *
     * @return string
     */
    public function getSendMethod(): string
    {
        $handler = $this->formConfig['sendMethod'] ?? Form::HANDLER_EMAIL;
        // Allow only secure handlers to send patron's barcode
        if (($this->formConfig['includeBarcode'] ?? false)
            && !in_array($handler, $this->secureHandlers)
        ) {
            throw new BadConfig("includeBarcode not allowed with $handler");
        }
        return $handler;
    }

    /**
     * Return API settings
     *
     * @return string
     */
    public function getApiSettings(): array
    {
        return $this->formConfig['apiSettings'] ?? [];
    }

    /**
     * Get form element class.
     *
     * @param string $type Element type
     *
     * @return string|null
     */
    protected function getFormElementClass($type)
    {
        if ($type === 'hidden') {
            return '\Laminas\Form\Element\Hidden';
        }

        return parent::getFormElementClass($type);
    }

    /**
     * Get form element/field names
     *
     * @return array
     */
    public function getFormFields()
    {
        $elements = $this->getFormElements($this->getFormConfig($this->formId));
        $fields = [];
        foreach ($elements as $el) {
            if ($el['type'] === 'submit') {
                continue;
            }
            $fields[] = $el['name'];
        }

        return $fields;
    }

    /**
     * Get form elements
     *
     * @param array $config Form configuration
     *
     * @return array
     */
    protected function getFormElements($config)
    {
        $elements = parent::getFormElements($config);

        $includeRecordData = $this->formId === self::RECORD_FEEDBACK_FORM
          || $this->isRecordRequestFormWithBarcode();

        if ($includeRecordData) {
            // Add hidden fields for record data
            foreach (['record_id', 'record', 'record_info'] as $key) {
                $elements[$key]
                    = ['type' => 'hidden', 'name' => $key, 'value' => null];
            }
        }

        return $elements;
    }

    /**
     * Parse form configuration.
     *
     * @param string $formId Form id
     * @param array  $config Configuration
     * @param array  $params Additional form parameters.
     *
     * @return array
     */
    protected function parseConfig($formId, $config, $params)
    {
        $elements = parent::parseConfig($formId, $config, $params);

        if (!empty($this->formConfig['hideSenderInfo'])) {
            // Remove default sender info fields
            $filtered = [];
            $configFieldNames = array_column($config['fields'] ?? [], 'name');
            foreach ($elements as $el) {
                if (isset($el['group']) && $el['group'] === '__sender__'
                    && !in_array($el['name'] ?? '', $configFieldNames)
                ) {
                    continue;
                }
                $filtered[] = $el;
            }
            $elements = $filtered;
        } else {
            // Add help text for default sender name & email fields
            if (!empty($this->formConfig['senderInfoHelp'])) {
                $help = $this->formConfig['senderInfoHelp'];
                foreach ($elements as &$el) {
                    if (isset($el['group']) && $el['group'] === '__sender__') {
                        $el['help'] = $help;
                        break;
                    }
                }
            }
        }

        return $elements;
    }

    /**
     * Return name of form element that is used as email recipient.
     *
     * @param array $config Form elements configuration.
     *
     * @return string|null
     */
    protected function getRecipientField($config)
    {
        foreach ($config as $el) {
            // Allow only select elements
            if ($el['recipient'] ?? false && $el['type'] === 'select') {
                return $el['name'];
            }
        }
        return null;
    }

    /**
     * Return a list of field names to read from settings file.
     *
     * @return array
     */
    protected function getFormSettingFields()
    {
        $fields = parent::getFormSettingFields();

        $fields = array_merge(
            $fields,
            [
                'apiSettings',
                'hideRecipientInfo',
                'hideSenderInfo',
                'includeBarcode',
                'senderInfoHelp',
                'sendMethod',
            ]
        );

        return $fields;
    }

    /**
     * Return a list of field names to read from form element settings.
     *
     * @return array
     */
    protected function getFormElementSettingFields()
    {
        $fields = parent::getFormElementSettingFields();
        $fields[] = 'recipient';

        return $fields;
    }

    /**
     * Get form configuration
     *
     * @param string $formId Form id
     *
     * @return mixed null|array
     * @throws Exception
     */
    protected function getFormConfig($formId = null)
    {
        $confName = 'FeedbackForms.yaml';
        $viewConfig = $finnaConfig = null;

        $finnaConfig = $this->yamlReader->getFinna($confName, 'config/finna');
        $viewConfig = $this->yamlReader->getFinna($confName, 'config/vufind');

        if (!$formId) {
            $formId = $viewConfig['default'] ?? $finnaConfig['default'] ?? null;
            if (!$formId) {
                return null;
            }
        }

        $config = $finnaConfig['forms'][$formId] ?? [];
        $viewConfig = $viewConfig['forms'][$formId] ?? null;

        if (!$viewConfig) {
            return $config;
        }

        if (isset($config['allowLocalOverride'])
            && $config['allowLocalOverride'] === false
        ) {
            return $config;
        }

        // Merge local configuration to Finna default
        // - 'fields' section as such
        // - everything else key by key
        $data = array_replace_recursive($config, $viewConfig);
        $data['fields'] = $viewConfig['fields'] ?? $config['fields'];

        return $data;
    }

    /**
     * Is this form allowed to send user's library card barcode
     * along with the form data?
     *
     * @return boolean
     */
    protected function isRecordRequestFormWithBarcode() : bool
    {
        return in_array($this->formId, $this->recordRequestFormsWithBarcode);
    }
}
