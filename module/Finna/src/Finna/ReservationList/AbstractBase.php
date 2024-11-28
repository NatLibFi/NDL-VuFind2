<?php

/**
 * Disec connection handler
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

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Laminas\Mvc\Controller\Plugin\Params;
use Psr\Container\ContainerInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Form\Form;
use VuFind\Service\GetServiceTrait;

/**
 * Disec connection handler
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class AbstractBase implements ConnectionInterface
{
    use GetServiceTrait;

    /**
     * Default unknown status
     *
     * @var string
     */
    public const STATUS_UNKNOWN = 'UNKNOWN';

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
     * Constructor
     *
     * @param ContainerInterface $serviceLocator Service locator used with GetServiceTrait
     */
    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Places an order
     *
     * @param Params              $params Parameters containing data from posting place order form
     * @param UserEntityInterface $user   User entity
     * @param Form                $form   Form posted when submitting the order
     *
     * @return array [external_id: Id in external service or null, success: true or false]
     */
    public function placeOrder(Params $params, UserEntityInterface $user, Form $form = null): array
    {
        $params->getController()->getRequest()->getPost()->set('recipient', $this->recipients);
        $formPluginManager = $this->getService(\VuFind\Form\Handler\PluginManager::class);
        $result = $formPluginManager->get($this->configuredHandler)->handle($form, $params, $user);
        return [
        'success' => $result,
        'external_id' => null,
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
        return self::STATUS_UNKNOWN;
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
