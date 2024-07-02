<?php

/**
 * Class ReservationListEmail
 *
 * PHP version 8.1
 *
 * Copyright (C) The National Library of Finland 2024.
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
 * @package  Form
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

declare(strict_types=1);

namespace Finna\Form\Handler;

use Finna\ReservationList\ReservationListService;
use Laminas\Config\Config;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Mailer\Mailer;

/**
 * Class ReservationListEmail
 *
 * @category VuFind
 * @package  Form
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ReservationListEmail extends \Finna\Form\Handler\Email
{

  /**
   * Constructor.
   * 
   * @param RendererInterface      $viewRenderer           View renderer
   * @param Config                 $config                 Reservation List Configuration
   * @param Mailer                 $mailer                 Mailer
   * @param ReservationListService $reservationListService Reservation list service
   * @param Config                 $reservationListConfig  Reservation list configuration
   */
  public function __construct(
    protected RendererInterface $viewRenderer,
    protected Config $config,
    protected Mailer $mailer,
    protected ReservationListService $reservationListService,
    protected Config $reservationListConfig
  )
  {
    parent::__construct($viewRenderer, $config, $mailer);
  }
    /**
     * Get data from submitted form and process them.
     *
     * @param \VuFind\Form\Form                     $form   Submitted form
     * @param \Laminas\Mvc\Controller\Plugin\Params $params Request params
     * @param ?\VuFind\Db\Row\User                  $user   Authenticated user
     *
     * @return bool
     */
    public function handle(
      \VuFind\Form\Form $form,
      \Laminas\Mvc\Controller\Plugin\Params $params,
      ?\VuFind\Db\Row\User $user = null
    ): bool {
      if (
        !$form instanceof \Finna\Form\ReservationListForm ||
        !$user ||
        $this->reservationListService->userHasAuthority($user, $form->getListId())
      ) {
        return false;
      }
      $records = $this->reservationListService->getRecordsForList($user, $form->getListId());
      $fields = $form->mapRequestParamsToFieldValues($params->fromPost());
      $emailMessage = $this->viewRenderer->partial(
          'Email/form.phtml',
          compact('fields')
      );

      [$senderName, $senderEmail] = $this->getSender($form);

      $replyToName = $params->fromPost(
          'name',
          $user ? trim($user->firstname . ' ' . $user->lastname) : null
      );
      $replyToEmail = $params->fromPost(
          'email',
          $user ? $user->email : null
      );
      $recipients = $form->getRecipient($params->fromPost());
      $emailSubject = $form->getEmailSubject($params->fromPost());

      $result = true;
      foreach ($recipients as $recipient) {
        $success = $this->sendEmail(
            $recipient['name'],
            $recipient['email'],
            $senderName,
            $senderEmail,
            $replyToName,
            $replyToEmail,
            $emailSubject,
            $emailMessage
        );

        $result = $result && $success;
      }
      return $result;
  }
}
