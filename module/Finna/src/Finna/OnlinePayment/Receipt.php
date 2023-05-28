<?php

/**
 * Online payment receipt
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\OnlinePayment;

use Finna\Db\Row\Fee;
use Finna\Db\Row\Transaction;
use Laminas\Mail\Address;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use Laminas\Router\RouteInterface;
use Laminas\View\Renderer\RendererInterface;
use TCPDF;
use VuFind\Date\Converter as DateConverter;
use VuFind\Db\Row\User;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Mailer\Mailer;
use VuFind\Service\CurrencyFormatter;

/**
 * Online payment service
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Receipt implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * Main configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Data source configuration
     *
     * @var array
     */
    protected $dataSourceConfig;

    /**
     * Date converter
     *
     * @var DateConverter
     */
    protected $dateConverter;

    /**
     * Currency formatter
     *
     * @var CurrencyFormatter
     */
    protected $currencyFormatter;

    /**
     * Router
     *
     * @var RouteInterface
     */
    protected $router;

    /**
     * Mailer
     *
     * @var Mailer
     */
    protected $mailer;

    /**
     * View renderer
     *
     * @var RendererInterface;
     */
    protected $renderer;

    /**
     * Left margin of PDF (millimeters)
     *
     * @var int
     */
    protected $left = 10;

    /**
     * Max x position of PDF (millimeters)
     *
     * @var int
     */
    protected $right = 200;

    /**
     * Max y position of PDF (millimeters)
     */
    protected $bottom = 280;

    /**
     * Constructor.
     *
     * @param array             $config            Main configuration
     * @param array             $dsConfig          Data source configuration
     * @param DateConverter     $dateConverter     Date converter
     * @param CurrencyFormatter $currencyFormatter Currency formatter
     * @param RouteInterface    $router            Router
     * @param Mailer            $mailer            Mailer
     * @param RendererInterface $renderer          View renderer
     */
    public function __construct(
        array $config,
        array $dsConfig,
        DateConverter $dateConverter,
        CurrencyFormatter $currencyFormatter,
        RouteInterface $router,
        Mailer $mailer,
        RendererInterface $renderer
    ) {
        $this->config = $config;
        $this->dataSourceConfig = $dsConfig;
        $this->dateConverter = $dateConverter;
        $this->currencyFormatter = $currencyFormatter;
        $this->router = $router;
        $this->mailer = $mailer;
        $this->renderer = $renderer;
    }

    /**
     * Create a receipt PDF
     *
     * @param Transaction $transaction Transaction
     *
     * @return array
     */
    public function createReceiptPDF(Transaction $transaction): array
    {
        $source = $this->getSource($transaction);
        $sourceName = $this->getSourceName($transaction);

        $paidDate = $this->dateConverter->convertToDisplayDateAndTime(
            'Y-m-d H:i:s',
            $transaction->paid
        );

        $dsConfig = $this->dataSourceConfig[$source] ?? [];
        if ($orgId = $dsConfig['onlinePayment']['organisationInfoId'] ?? '') {
            $contactInfo = $this->router->assemble(
                [],
                [
                    'name' => 'organisationinfo-home',
                    'query' => [
                        'id' => $orgId,
                    ],
                    'force_canonical' => true,
                ]
            );
        } else {
            $contactInfo = $dsConfig['onlinePayment']['contactInfo'] ?? '';
        }
        $businessId = $dsConfig['onlinePayment']['businessId'] ?? '';
        $organizationBusinessIdMappings = [];
        if ($map = $dsConfig['onlinePayment']['organizationBusinessIdMappings'] ?? '') {
            foreach (explode(':', $map) as $item) {
                $parts = explode('=', $item, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                $organizationBusinessIdMappings[trim($parts[0])] = trim($parts[1]);
            }
        }
        // Check if we have recipient organizations:
        $hasFineOrgs = false;
        foreach ($transaction->getFines() as $fine) {
            $fineOrg = $fine->organization;
            if ($fineOrg && ($organizationBusinessIdMappings[$fineOrg] ?? false)) {
                $hasFineOrgs = true;
                break;
            }
        }

        $heading = $this->translate('Payment::breakdown_title') . " - $sourceName";
        [$language] = explode('-', $this->getTranslatorLocale(), 2);
        $languageConfig = [
            'a_meta_charset' => 'utf-8',
            'a_meta_dir' => 'ltr',
            'a_meta_language' => $language,
            'w_page' => 'page',
        ];
        $pdf = new TCPDF();
        $pdf->setLanguageArray($languageConfig);
        $pdf->SetCreator('Finna');
        $pdf->SetTitle($heading . ' - ' . $paidDate);
        $pdf->SetMargins($this->left, 18);
        $pdf->SetHeaderMargin(10);
        $pdf->SetHeaderData('', 0, $heading);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Print information array:
        $pdf->setY(25);
        if (!$hasFineOrgs) {
            $this->addInfo($pdf, 'Payment::Recipient', $sourceName . ($businessId ? " ($businessId)" : ''));
        }
        $this->addInfo($pdf, 'Payment::Date', $paidDate);
        $this->addInfo($pdf, 'Payment::Identifier', $transaction->transaction_id);
        if ($contactInfo) {
            $this->addInfo($pdf, 'Payment::Contact Information', $contactInfo);
        }

        // Print lines:
        $pdf->SetY($pdf->GetY() + 10);
        $this->addHeaders($pdf, $hasFineOrgs);
        // Account for the "Total" line:
        $linesBottom = $this->bottom - 7;
        foreach ($transaction->getFines() as $fine) {
            $savePDF = clone $pdf;

            $fineOrg = $fine->organization ?? '';
            $lineBusinessId = $fineOrg ? ($organizationBusinessIdMappings[$fineOrg] ?? '') : '';
            $this->addLine($pdf, $fine, $sourceName, $businessId, $lineBusinessId, $hasFineOrgs);
            // If we exceed bottom, revert and add a new page:
            if ($pdf->GetY() > $linesBottom) {
                $pdf = $savePDF;
                $pdf->AddPage();
                $pdf->SetY(25);
                $this->addHeaders($pdf, $hasFineOrgs);
                $this->addLine($pdf, $fine, $sourceName, $businessId, $lineBusinessId, $hasFineOrgs);
            }
        }
        $pdf->SetY($pdf->GetY() + 1);
        $pdf->SetFont('helvetica', 'B', 10);
        $amount = $this->currencyFormatter->convertToDisplayFormat(
            $transaction->amount / 100.00,
            $transaction->currency
        );
        $pdf->Cell(
            190,
            0,
            $this->translate('Payment::Total') . " $amount",
            0,
            1,
            'R'
        );

        // Print VAT summary:
        $savePDF = clone $pdf;
        $this->addVATSummary($pdf, $transaction);
        if ($pdf->GetY() > $this->bottom) {
            $pdf = $savePDF;
            $pdf->AddPage();
            $this->addVATSummary($pdf, $transaction);
        }

        $date = strtotime($transaction->paid);
        return [
            'pdf' => $pdf->getPDFData(),
            'filename' => $heading . ' - ' . date('Y-m-d H-i', $date),
        ];
    }

    /**
     * Send receipt by email
     *
     * @param User        $user          User
     * @param array       $patronProfile Patron information
     * @param Transaction $transaction   Transaction
     *
     * @return void
     *
     * @todo Add attachment support to Mailer's send method
     */
    public function sendEmail(User $user, array $patronProfile, Transaction $transaction): void
    {
        $recipients = array_unique(
            array_filter(
                [
                    trim($patronProfile['email'] ?? ''),
                    trim($user->email),
                ]
            )
        );
        if (!$recipients) {
            return;
        }

        $data = $this->createReceiptPDF($transaction);

        $this->mailer->setMaxRecipients(2);
        $from = $this->config['Site']['email'];
        $fromOverride = $this->mailer->getFromAddressOverride();

        $replyTo = null;
        if ($fromOverride && $fromOverride !== $from) {
            // Add the original from address as the reply-to address
            $replyTo = $from;
            $from = new Address($from);
            $name = $from->getName();
            if (!$name) {
                [$fromPre] = explode('@', $from->getEmail());
                $name = $fromPre ? $fromPre : null;
            }
            $from = new Address($fromOverride, $name);
        }

        $message = $this->mailer->getNewMessage()
            ->addFrom($from)
            ->addTo($recipients)
            ->setSubject(
                $this->translate('Payment::breakdown_title') . ' - ' . $this->getSourceName($transaction)
            );
        if ($replyTo) {
            $message->addReplyTo($replyTo);
        }

        $pdf = new MimePart($data['pdf']);
        $pdf->type = 'application/pdf';
        $pdf->charset = 'utf-8';
        $pdf->encoding = Mime::ENCODING_BASE64;
        $pdf->disposition = 'inline; filename="' .
            addcslashes($data['filename'], '"') . '"';

        $messageContent = $this->renderer->partial(
            'Email/receipt.phtml',
            compact('user', 'patronProfile', 'transaction')
        );
        $text = new MimePart($messageContent);
        $text->type = Mime::TYPE_TEXT;
        $text->charset  = 'utf-8';
        $text->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

        $body = new MimeMessage();
        $body->setParts([$text, $pdf]);

        $message->setBody($body);
        $contentTypeHeader = $message->getHeaders()->get('Content-Type');
        $contentTypeHeader->setType('multipart/mixed');

        $this->mailer->getTransport()->send($message);
    }

    /**
     * Add info row
     *
     * @param TCPDF  $pdf     PDF
     * @param string $heading Heading
     * @param string $value   Value
     *
     * @return void
     */
    protected function addInfo($pdf, $heading, $value): void
    {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 0, $this->translate($heading));
        $pdf->SetFont('helvetica', '', 10);
        if (preg_match('/^https?:\/\/([^\s]+)$/', $value, $matches)) {
            // Create link:
            $pdf->Write(0, $matches[1], $value);
        } else {
            $pdf->Cell(120, 0, $value);
        }
        $pdf->Ln();
    }

    /**
     * Add item table headers
     *
     * @param TCPDF $pdf       PDF
     * @param bool  $recipient Whether to add recipient column
     *
     * @return void
     */
    protected function addHeaders(TCPDF $pdf, bool $recipient): void
    {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 0, $this->translate('Payment::Identifier'), 0, 0);
        $pdf->Cell(40, 0, $this->translate('Payment::Type'), 0, 0);
        $pdf->Cell($recipient ? 50 : 100, 0, $this->translate('Payment::Details'), 0, 0);
        if ($recipient) {
            $pdf->Cell(50, 0, $this->translate('Payment::Recipient'), 0, 0);
        }
        $pdf->Cell(20, 0, $this->translate('Payment::Fee'), 0, 1, 'R');
        $pdf->SetFont('helvetica', '', 10);
        $y = $pdf->GetY() + 1;
        $pdf->Line($this->left, $y, $this->right, $y);
        $pdf->SetY($y + 1);
    }

    /**
     * Add item table line
     *
     * @param TCPDF  $pdf            PDF
     * @param Fee    $fine           Fee or fine
     * @param string $sourceName     Source name
     * @param string $businessId     Source business ID
     * @param string $lineBusinessId Line business ID
     * @param bool   $recipient      Whether to add recipient column
     *
     * @return void
     */
    protected function addLine(
        TCPDF $pdf,
        Fee $fine,
        string $sourceName,
        string $businessId,
        string $lineBusinessId,
        bool $recipient
    ): void {
        $type = $fine->type;
        $type = $this->translate("fine_status_$type", [], $this->translate("status_$type", [], $type));

        $curY = $pdf->GetY();

        $pdf->MultiCell(28, 0, $fine->fine_id ?? '', 0, 'L');
        $nextY = $pdf->GetY();

        $pdf->SetXY($this->left + 30, $curY);
        $pdf->MultiCell(38, 0, $type, 0, 'L');
        $nextY = max($nextY, $pdf->GetY());

        $pdf->SetXY($this->left + 70, $curY);
        $pdf->MultiCell($recipient ? 48 : 98, 0, $fine->title ?? '', 0, 'L');
        $nextY = max($nextY, $pdf->GetY());

        if ($recipient) {
            if (($fineOrg = $fine->organization) && $lineBusinessId) {
                $recipient = $this->translate('Payment::organization_' . $fineOrg, [], $fineOrg)
                    . " ($lineBusinessId)";
            } else {
                $recipient = $sourceName . ($businessId ? " ($businessId)" : '');
            }
            $pdf->SetXY($this->left + 120, $curY);
            $pdf->MultiCell(48, 0, $recipient, 0, 'L');
            $nextY = max($nextY, $pdf->GetY());
        }

        $pdf->SetXY($this->left + 170, $curY);
        $pdf->Cell(
            20,
            0,
            $this->currencyFormatter->convertToDisplayFormat($fine->amount / 100.00, $fine->currency),
            0,
            0,
            'R'
        );
        $pdf->setY($nextY + 2);
    }

    /**
     * Add VAT summary
     *
     * @param TCPDF       $pdf         PDF
     * @param Transaction $transaction Transaction
     *
     * @return void
     */
    protected function addVATSummary(TCPDF $pdf, Transaction $transaction): void
    {
        $amount = $this->currencyFormatter->convertToDisplayFormat(
            $transaction->amount / 100.00,
            $transaction->currency
        );

        $pdf->SetY($pdf->GetY() + 15);
        $pdf->SetFont('helvetica', 'B', 10);
        $vatLeft = $this->left + 50;
        $pdf->SetX($vatLeft);
        $pdf->Cell(30, 0, $this->translate('Payment::VAT Breakdown'), 0, 0, 'L');
        $pdf->Cell(20, 0, $this->translate('Payment::VAT Percent'));
        $pdf->Cell(30, 0, $this->translate('Payment::Excluding VAT'), 0, 0, 'R');
        $pdf->Cell(30, 0, $this->translate('Payment::VAT'), 0, 0, 'R');
        $pdf->Cell(30, 0, $this->translate('Payment::Including VAT'), 0, 1, 'R');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetX($vatLeft);
        $y = $pdf->GetY() + 1;
        $pdf->Line($vatLeft, $y, $vatLeft + 30, $y, ['dash' => '1,2']);
        $pdf->Line($vatLeft + 30, $y, $this->right, $y, ['dash' => 0]);
        $pdf->SetXY($vatLeft + 30, $y + 1);
        $pdf->Cell(20, 0, '0 %');
        $pdf->Cell(30, 0, $amount, 0, 0, 'R');
        $pdf->Cell(30, 0, $this->currencyFormatter->convertToDisplayFormat(0, $transaction->currency), 0, 0, 'R');
        $pdf->Cell(30, 0, $amount, 0, 1, 'R');
    }

    /**
     * Get source identifier from transaction
     *
     * @param Transaction $transaction Transaction
     *
     * @return string
     */
    protected function getSource(Transaction $transaction): string
    {
        [$source] = explode('.', $transaction->cat_username);
        return $source;
    }

    /**
     * Get source name from transaction
     *
     * @param Transaction $transaction Transaction
     *
     * @return string
     */
    protected function getSourceName(Transaction $transaction): string
    {
        $source = $this->getSource($transaction);
        return $this->translate('source_' . $source, [], $source);
    }
}
