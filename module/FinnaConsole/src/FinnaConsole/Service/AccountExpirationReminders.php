<?php
/**
 * Console service for reminding users x days before account expiration
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2015-2016.
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
 * @package  Service
 * @author   Jyrki Messo <jyrki.messo@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace FinnaConsole\Service;

use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\TemplatePathStack;

use DateTime;
use DateInterval;

/**
 * Console service for reminding users x days before account expiration
 *
 * @category VuFind
 * @package  Service
 * @author   Jyrki Messo <jyrki.messo@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class AccountExpirationReminders extends AbstractService
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Current view local configuration directory.
     *
     * @var string
     */
    protected $baseDir = null;

    /**
     * Base directory for all views.
     *
     * @var string
     */
    protected $viewBaseDir = null;

    /**
     * View renderer
     *
     * @var Zend\View\Renderer\PhpRenderer
     */
    protected $renderer = null;

    /**
     * Table for user accounts
     *
     * @var \VuFind\Db\Table\User
     */
    protected $table = null;

    /**
     * ServiceManager
     *
     * ServiceManager is used for creating VuFind\Mailer objects as needed
     * (mailer is not shared as its connection might time out otherwise).
     *
     * @var ServiceManager
     */
    protected $serviceManager = null;

    /**
     * Translator
     *
     * @var Zend\I18n\Translator\Translator
     */
    protected $translator = null;

    /**
     * UrllHelper
     *
     * @var urlHelper
     */
    protected $urlHelper = null;

    /**
     * Current institution.
     *
     * @var string
     */
    protected $currentInstitution = null;

    /**
     * Datasource configuration
     *
     * @var \Zend\Config\Config
     */
    protected $datasourceConfig = null;

    /**
     * ConfigReader
     *
     * @var \VuFind\Config
     */
    protected $configReader = null;

    /**
     * Constructor
     *
     * @param Finna\Db\Table\User            $table          User table.
     * @param Zend\View\Renderer\PhpRenderer $renderer       View renderer.
     * @param VuFind\Config                  $configReader   Config reader.
     * @param VuFind\Translator              $translator     Translator.
     * @param ServiceManager                 $serviceManager Service manager.
     */
    public function __construct(
        $table, $renderer, $configReader, $translator, $serviceManager
    ) {
        $this->table = $table;
        $this->renderer = $renderer;
        $this->datasourceConfig = $configReader->get('datasources');
        $this->translator = $translator;
        $this->serviceManager = $serviceManager;
        $this->urlHelper = $renderer->plugin('url');
    }

    /**
     * Run service.
     *
     * @param array $arguments Command line arguments.
     *
     * @return boolean success
     */
    public function run($arguments)
    {
        if (count($arguments) < 5
            || (int) $arguments[2] < 180
        ) {
            $this->msg($this->getUsage());
            return false;
        }

        if (! $this->collectScriptArguments($arguments)) {
            $this->msg($this->getUsage());
            return false;
        }

        $siteConfig = \VuFind\Config\Locator::getLocalConfigPath("config.ini");
        $this->currentSiteConfig = parse_ini_file($siteConfig, true);

        $users = $this->getUsersToRemind(
            $this->expirationDays, $this->remindDaysBefore, $this->remindingFrequency
        );
        $count = 0;

        foreach ($users as $user) {
            $this->msg("Sending expiration reminder for user " . $user->username);
            $this->sendAccountExpirationReminder($user, $this->expirationDays);
            $count++;
        }

        if ($count === 0) {
            $this->msg('No user accounts to remind.');
        } else {
            $this->msg("$count expiring user accounts reminded.");
        }

        return true;
    }

    /**
     * Returns all users that have not been active for given amount of days.
     *
     * @param int $days             Expiration limit (in days) for user accounts
     * @param int $remindDaysBefore How many days before expiration reminder starts
     * @param int $frequency        The freqency in days for reminding the user
     *
     * @return User[] users to remind on expiration
     */
    protected function getUsersToRemind($days, $remindDaysBefore, $frequency)
    {
        $remindingDaysBegin = [];
        $remindingDaysEnd = [];

        for ($x = $remindDaysBefore; $x > 0; $x -= $frequency) {
            $remindingDaysBegin[] = date(
                'Y-m-d 00:00:00', strtotime(sprintf('-%d days', (int) $days - $x))
            );
            $remindingDaysEnd[] = date(
                'Y-m-d 23:59:59', strtotime(sprintf('-%d days', (int) $days - $x))
            );
        }
      
        $timePeriods = "";

        for ($i = 0; $i < count($remindingDaysBegin); $i++) {
            if (! empty($timePeriods)) {
                $timePeriods .= " OR ";
            }
            $timePeriods .= "(finna_last_login >= '"
                         . $remindingDaysBegin[$i] . "' AND ";
            $timePeriods .= "finna_last_login <= '"
                         . $remindingDaysEnd[$i] . "')";
        }

        return $this->table->select(
            function (Select $select) use ($timePeriods) {
                $select->where->notLike('username', 'deleted:%');
                $select->where($timePeriods);
                $select->where->notEqualTo(
                    'finna_last_login',
                    '0000-00-00 00:00:00'
                );
            }
        );
    }

    /**
     * Send account expiration reminder for a user.
     *
     * @param \Finna\Db\Table\Row\User $user            User.
     * @param int                      $expiration_days Amount of days when 
     *                                                  account expires
     *
     * @return boolean success.
     */
    protected function sendAccountExpirationReminder($user,$expiration_days)
    {
        if (!$user->email || trim($user->email) == '') {
            $this->msg(
                "User {$user->username} (id {$user->id})"
                . ' does not have an email address, bypassing expiration reminders'
            );
            return false;
        }

        list($userInstitution,$userName) = explode(':', $user['username'], 2);

        if (!$this->currentInstitution
            || $userInstitution != $this->currentInstitution
        ) {
            $templateDirs = [
                "{$this->baseDir}/themes/finna/templates",
            ];
            if (!$viewPath = $this->resolveViewPath($userInstitution)) {
                $this->err(
                    "Could not resolve view path for user {$user->username}"
                    . " (id {$user->id})"
                );
                return false;
            } else {
                $templateDirs[] = "$viewPath/themes/custom/templates";
            }

            $this->currentInstitution = $userInstitution;
            $this->currentViewPath = $viewPath;

            $resolver = new AggregateResolver();
            $this->renderer->setResolver($resolver);
            $stack = new TemplatePathStack(['script_paths' => $templateDirs]);
            $resolver->attach($stack);

            $siteConfig = $viewPath . '/local/config/vufind/config.ini';
            $this->currentSiteConfig = parse_ini_file($siteConfig, true);
        }

        $expiration_datetime = new DateTime($user->finna_last_login);
        $expiration_datetime->add(new DateInterval('P' . $expiration_days . 'D'));

        $language = isset($this->currentSiteConfig['Site']['language'])
            ? $this->currentSiteConfig['Site']['language'] : 'fi';
        $validLanguages = array_keys($this->currentSiteConfig['Languages']);

        if (!empty($user->finna_language)
            && in_array($user->finna_language, $validLanguages)
        ) {
            $language = $user->finna_language;
        }

        $this->translator
            ->addTranslationFile('ExtendedIni', null, 'default', $language)
            ->setLocale($language);

        $login_path = $this->urlHelper->__invoke('myresearch-home');
        if (!$this->currentInstitution || $this->currentInstitution == 'national') {
            $this->currentInstitution = 'www';
        }
        
        $params = [
            'login_method' => $user->finna_auth_method,
            'username' => $userName,
            'firstname' => $user->firstname,
            'fullname' =>  $user->firstname . " " . $user->lastname,
            'email' => $user->email,
            'expiration_date' =>  $expiration_datetime->format('d.m.Y'),
            'login_link' => 'https://' . $this->currentInstitution .
                            '.finna.fi' . $login_path
        ];

        $subject = $this->translate(
            'account_expiration_subject',
            ['%%expiration_date%%' => $params['expiration_date']]
        );

        
        $message = $this->renderer->render(
            'Email/account-expiration-reminder.phtml', $params
        );

        try {
            $to = $user->email;
            $from = $this->currentSiteConfig['Site']['email'];

            $this->serviceManager->get('VuFind\Mailer')->send(
                $to, $from, $subject, $message
            );
        } catch (\Exception $e) {
            $this->err(
                "Failed to send expiration reminder to user {$user->username} "
                . " (id {$user->id})"
            );
            $this->err('   ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Collect command line arguments.
     *
     * @param array $arguments Arguments
     *
     * @return void
     */
    protected function collectScriptArguments($arguments)
    {
        // Current view local configuration directory
        $this->baseDir = isset($arguments[0]) ? $arguments[0] : false;

        // Current view local basedir
        $this->viewBaseDir = isset($arguments[1]) ? $arguments[1] : false;

        // Inactive user account will expire in expirationDays days
        $this->expirationDays = (isset($arguments[2])
                                  && $arguments[2] >= 180) ? $arguments[2] : false;

        // Start reminding remindDaysBefore before expiration
        $this->remindDaysBefore = (isset($arguments[3]) &&
                                   $arguments[3] > 0) ? $arguments[3] : false;

        // Remind between remindingFrequency days when reminding period has started
        $this->remindingFrequency = (isset($arguments[4])
                                     && $arguments[4] > 0) ? $arguments[4] : false;
        if (!$this->baseDir
            || !$this->viewBaseDir
            || !$this->expirationDays
            || !$this->remindDaysBefore
            || !$this->remindingFrequency
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get usage information.
     *
     * @return string
     */
    protected function getUsage()
    {
        // @codingStandardsIgnoreStart
        return <<<EOT
Usage:
  php index.php util expiration_reminders <vufind_dir> <view_dir> <expiration_days> <remind_days_before> <frequency>

  Sends a reminder for those users whose account will expire in <remind_days_before> days.
    vufind_dir          VuFind base installation directory
    view_dir            View directory
    expiration_days     After how many inactive days a user account will expire.
                        Values less than 180 are not valid.
    remind_days_before  Begin reminding the user x days before the actual expiration
    frequency           How often (in days) the user will be reminded

EOT;
        // @codingStandardsIgnoreEnd
    }
}
