<?php

/**
 * FeedbackForm connection handler
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\ReservationList;

use Exception;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Form\Form;

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
     * Configured handler to handle form post, defaults to email handler
     *
     * @var string
     */
    protected string $configuredHandler = 'email';

    /**
     * Places an order
     *
     * @param array|Params        $postValues Key value pairs of post parameters to send or params plugin
     * @param UserEntityInterface $user       User entity
     * @param Form                $form       Form posted when submitting the order
     *
     * @return array [external_id: Id in external service or null, success: true or false]
     */
    public function placeOrder(array|Params $postValues, UserEntityInterface $user, Form $form = null): array
    {
        if (!($postValues instanceof Params)) {
            throw new Exception('ReservationList FeedbackForm: Illegal parameter type.');
        }
        $postValues->getController()->getRequest()->getPost()->set('recipient', $this->recipients);
        $result = $this->getService(\VuFind\Form\Handler\PluginManager::class)
            ->get($this->configuredHandler)->handle($form, $postValues, $user);
        return [
            'success' => $result,
            'external_id' => null,
        ];
    }

    /**
     * Initialize connection handler
     *
     * @param array $config List specific configuration from ReservationList.yaml
     *
     * @return static
     * @throws \Exception If Database connection is not configured properly
     */
    public function init(array $config): self
    {
        try {
            $this->recipients = $config['Recipient'];
            $this->configuredHandler = $config['Connection']['handler'] ?? 'email';
        } catch (\Exception $e) {
            throw new \Exception('Database: Invalid configuration');
        }
        return $this;
    }
}
