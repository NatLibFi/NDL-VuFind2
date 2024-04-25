<?php

/**
 * Console service for processing unregistered online payments.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2016-2023.
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
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace FinnaConsole\Command\Util;

use Finna\Db\Row\Transaction as TransactionRow;
use Finna\Db\Table\TransactionEventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function intval;

/**
 * Console service for processing unregistered online payments.
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class OnlinePaymentMonitor extends AbstractUtilCommand
{
    use \Finna\OnlinePayment\OnlinePaymentEventLogTrait;
    use \Finna\OnlinePayment\OnlinePaymentHandlerTrait;

    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/online_payment_monitor';

    /**
     * ILS connection.
     *
     * @var \VuFind\ILS\Connection
     */
    protected $ils;

    /**
     * Datasource configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $datasourceConfig;

    /**
     * Transaction table
     *
     * @var \Finna\Db\Table\Transaction
     */
    protected $transactionTable;

    /**
     * User account table
     *
     * @var \Finna\Db\Table\User
     */
    protected $userTable;

    /**
     * Mailer
     *
     * @var \VuFind\Mailer\Mailer
     */
    protected $mailer;

    /**
     * View renderer
     *
     * @var \Laminas\View\Renderer\PhpRenderer
     */
    protected $viewRenderer;

    /**
     * Number of hours before considering unregistered transactions to be expired.
     *
     * @var int
     */
    protected $expireHours = 3;

    /**
     * Sender email address for notification of expired transactions.
     *
     * @var string
     */
    protected $fromEmail = '';

    /**
     * Transaction event log table
     *
     * @var TransactionEventLog
     */
    protected $eventLogTable;

    /**
     * Constructor
     *
     * @param \VuFind\ILS\Connection             $ils              Catalog connection
     * @param \Finna\Db\Table\Transaction        $transactionTable Transaction table
     * @param \Finna\Db\Table\User               $userTable        User table
     * @param \Laminas\Config\Config             $dsConfig         Data source config
     * @param \Laminas\View\Renderer\PhpRenderer $viewRenderer     View renderer
     * @param \VuFind\Mailer\Mailer              $mailer           Mailer
     * @param TransactionEventLog                $eventLog         Transaction event log table
     */
    public function __construct(
        \VuFind\ILS\Connection $ils,
        \Finna\Db\Table\Transaction $transactionTable,
        \Finna\Db\Table\User $userTable,
        \Laminas\Config\Config $dsConfig,
        \Laminas\View\Renderer\PhpRenderer $viewRenderer,
        \VuFind\Mailer\Mailer $mailer,
        TransactionEventLog $eventLog
    ) {
        $this->ils = $ils;
        $this->transactionTable = $transactionTable;
        $this->userTable = $userTable;
        $this->datasourceConfig = $dsConfig;
        $this->viewRenderer = $viewRenderer;
        $this->mailer = $mailer;
        $this->eventLogTable = $eventLog;

        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription(
                'Validate unregistered online payment transactions and send error'
                    . ' notifications'
            )
            ->addArgument(
                'expire_hours',
                InputArgument::REQUIRED,
                'Number of hours before considering unregistered transaction to be'
                    . ' expired.'
            )
            ->addArgument(
                'from_email',
                InputArgument::REQUIRED,
                'Sender email address for notification of expired transactions'
            )
            ->addArgument(
                'report_interval_hours',
                InputArgument::REQUIRED,
                'Interval when to re-send report of unresolved transactions'
            )
            ->addArgument(
                'minimum_paid_age',
                InputArgument::OPTIONAL,
                "Minimum age of transactions in 'paid' status until they are"
                    . 'considered failed (seconds, default 120)',
                120
            )
            ->addOption(
                'no-email',
                null,
                InputOption::VALUE_NONE,
                'Disable sending of any email messages'
            );
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->expireHours = $input->getArgument('expire_hours');
        $this->fromEmail = $input->getArgument('from_email');
        $reportIntervalHours = $input->getArgument('report_interval_hours');
        $minimumPaidAge = intval($input->getArgument('minimum_paid_age'));
        $disableEmail = $input->getOption('no-email') ?: false;

        // Abort if we have an invalid minimum paid age.
        if ($minimumPaidAge < 10) {
            $output->writeln('Minimum paid age must be at least 10 seconds');
            return 1;
        }

        $this->msg('OnlinePayment monitor started');
        $expiredCnt = $failedCnt = $registeredCnt = $remindCnt = 0;
        $report = [];
        $failed = $this->transactionTable->getFailedTransactions($minimumPaidAge);
        foreach ($failed as $t) {
            $this->processTransaction(
                $t,
                $report,
                $registeredCnt,
                $expiredCnt,
                $failedCnt
            );
        }

        // Report paid and unregistered transactions whose registration
        // can not be re-tried:
        $unresolved = $this->transactionTable
            ->getUnresolvedTransactions($reportIntervalHours);
        foreach ($unresolved as $t) {
            $this->processUnresolvedTransaction($t, $report, $remindCnt);
        }

        if ($registeredCnt) {
            $this->msg("Total registered: $registeredCnt");
        }
        if ($expiredCnt) {
            $this->msg("Total expired: $expiredCnt");
        }
        if ($failedCnt) {
            $this->msg("Total failed: $failedCnt");
        }
        if ($remindCnt) {
            $this->msg("Total to be reminded: $remindCnt");
        }

        if (!$disableEmail) {
            $this->sendReports($report);
        }

        $this->msg('OnlinePayment monitor completed');

        return 0;
    }

    /**
     * Try to register a failed transaction.
     *
     * @param TransactionRow $t             Transaction
     * @param array          $report        Transactions to be reported.
     * @param int            $registeredCnt Number of registered transactions.
     * @param int            $expiredCnt    Number of expired transactions.
     * @param int            $failedCnt     Number of failed transactions.
     *
     * @return bool success
     */
    protected function processTransaction(
        $t,
        &$report,
        &$registeredCnt,
        &$expiredCnt,
        &$failedCnt
    ) {
        $this->msg(
            "Registering transaction id {$t->id} / {$t->transaction_id}"
            . " (status: {$t->complete} / {$t->status}, paid: {$t->paid})"
        );

        // Check if the transaction has not been registered for too long
        $now = new \DateTime();
        $paidTime = new \DateTime($t->paid);
        $diff = $now->diff($paidTime);
        $diffHours = ($diff->days * 24) + $diff->h;
        if ($diffHours > $this->expireHours) {
            // Transaction has expired
            if (!isset($report[$t->driver])) {
                $report[$t->driver] = 0;
            }
            $report[$t->driver]++;
            $expiredCnt++;

            $t->setReportedAndExpired();
            $this->addTransactionEvent($t->id, 'Marked as reported and expired');

            $this->msg('Transaction ' . $t->transaction_id . ' expired.');
            return true;
        }

        try {
            $user = null;
            if (!($patron = $this->getPatronForTransaction($t, $user))) {
                if ($user) {
                    $this->warn(
                        "Catalog login failed for user {$user->username} (id {$user->id}), card {$t->cat_username}"
                    );
                    $t->setRegistrationFailed('patron login error');
                    $this->addTransactionEvent($t->id, 'Patron login failed');
                } else {
                    $this->warn("Library card not found for user {$t->user_id}, card {$t->cat_username}");
                    $t->setRegistrationFailed('card not found');
                    $this->addTransactionEvent(
                        $t->id,
                        "Library card not found for user id {$t->user_id}",
                        [
                            'user_id' => $t->user_id,
                            'card' => $t->cat_username,
                        ]
                    );
                }
                $failedCnt++;
                return false;
            }

            if (!$this->markFeesAsPaidForPatron($patron, $t)) {
                $failedCnt++;
                return false;
            }
            $registeredCnt++;
            return true;
        } catch (\Exception $e) {
            $this->warn(
                "Exception while processing transaction {$t->id} for user id {$t->user_id}, card {$t->cat_username}: "
                . (string)$e
            );
            $this->addTransactionEvent(
                $t->id,
                'Exception while processing transaction',
                [
                    'exception' => (string)$e,
                ]
            );
            $failedCnt++;
            return false;
        }
    }

    /**
     * Process an unresolved transaction.
     *
     * @param \Finna\Db\Row\Transaction $t         Transaction
     * @param array                     $report    Transactions to be reported.
     * @param int                       $remindCnt Number of transactions to be
     * reported as unresolved.
     *
     * @return void
     */
    protected function processUnresolvedTransaction($t, &$report, &$remindCnt)
    {
        $this->msg("Transaction id {$t->transaction_id} still unresolved.");

        $t->setReportedAndExpired();
        if (!isset($report[$t->driver])) {
            $report[$t->driver] = 0;
        }
        $report[$t->driver]++;
        $remindCnt++;
    }

    /**
     * Send email reports of unresolved transactions
     * (that need to be resolved manually via AdminInterface).
     *
     * @param array $report Transactions to be reported.
     *
     * @return void
     */
    protected function sendReports($report)
    {
        $subject = 'Finna: ilmoitus tietokannan %s epäonnistuneista verkkomaksuista';

        foreach ($report as $driver => $cnt) {
            if ($cnt) {
                $settings = $this->ils->getConfig(
                    'onlinePayment',
                    ['id' => "$driver.123"]
                );
                if (!$settings || empty($settings['errorEmail'])) {
                    if (!empty($this->datasourceConfig[$driver]['feedbackEmail'])) {
                        $settings['errorEmail']
                            = $this->datasourceConfig[$driver]['feedbackEmail'];
                        $this->warn(
                            '  No error email for expired transactions defined for '
                            . "driver $driver, using feedback email ($cnt expired "
                            . 'transactions)'
                        );
                    } else {
                        $this->err(
                            '  No error email for expired transactions defined for '
                            . "driver $driver ($cnt expired transactions)",
                            '='
                        );
                        continue;
                    }
                }

                $email = $settings['errorEmail'];
                $this->msg(
                    "[$driver] Inform $cnt expired transactions "
                    . "for driver $driver to $email"
                );

                $params = [
                   'driver' => $driver,
                   'cnt' => $cnt,
                ];
                $messageSubject = sprintf($subject, $driver);

                $message = $this->viewRenderer
                    ->render('Email/online-payment-alert.phtml', $params);

                try {
                    $this->mailer->setMaxRecipients(0);
                    $this->mailer->send(
                        $email,
                        $this->fromEmail,
                        $messageSubject,
                        $message
                    );
                } catch (\Exception $e) {
                    $this->err(
                        "    Failed to send error email to staff: $email "
                            . "(driver: $driver)",
                        'Failed to send error email to staff'
                    );
                    $this->logException($e);
                    continue;
                }
            }
        }
    }

    /**
     * Log a payment info message
     *
     * @param string $msg Message
     *
     * @return void
     */
    protected function logPaymentInfo(string $msg): void
    {
        $this->msg($msg);
    }
}
