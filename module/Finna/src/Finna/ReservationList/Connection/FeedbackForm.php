<?php

/**
 * FeedbackForm connection handler
 *
 * PHP version 8
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\ReservationList\Connection;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Finna\ReservationList\Form\Form;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Db\Entity\UserEntityInterface;

/**
 * FeedbackForm connection handler
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class FeedbackForm extends AbstractBase
{
    /**
     * Recipients for email handler defined in ReservationList.yaml
     *
     * @var array
     */
    protected array $recipients;

    /**
     * Places an order
     *
     * @param Params              $params Params plugin
     * @param UserEntityInterface $user   User entity
     * @param Form                $form   Form posted when submitting the order
     *
     * @return array [external_id: Id in external service or null, success: true or false]
     */
    public function placeOrder(Params $params, UserEntityInterface $user, Form $form = null): array
    {
        // Assign external recipients as lists can contain different recipients even if form base is the same
        $form->setReservationListRecipients($this->recipients);
        $result = $form->getPrimaryHandler()->handle($form, $params, $user);
        return [
            'success' => $result,
            'external_id' => null,
            'pickup_date' => $params->fromPost('pickup_date', ''),
        ];
    }

    /**
     * Check list status. Used for external services.
     *
     * @param FinnaResourceListEntityInterface $list List to check for status
     * @param UserEntityInterface              $user Current logged in user
     *
     * @return string
     */
    public function getListStatus(FinnaResourceListEntityInterface $list, UserEntityInterface $user): string
    {
        $status = ReservationListStatus::UNKNOWN;
        return $status->getTranslationKey();
    }

    /**
     * Initialize connection handler
     *
     * @param array $config List specific configuration from ReservationList.yaml
     *
     * @return static
     * @throws \Exception If FeedbackForm connection is not configured properly
     */
    public function init(array $config): static
    {
        try {
            $this->recipients = $config['Recipient'];
        } catch (\Exception $e) {
            throw new \Exception('FeedbackForm: Invalid configuration');
        }
        return $this;
    }
}
