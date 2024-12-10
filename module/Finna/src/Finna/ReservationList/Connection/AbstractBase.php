<?php

/**
 * Connection abstract base
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

namespace Finna\ReservationList\Connection;

use Finna\Db\Entity\FinnaResourceListEntityInterface;
use Laminas\Mvc\Controller\Plugin\Params;
use Psr\Container\ContainerInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Form\Form;
use VuFind\Service\GetServiceTrait;

/**
 * Connection abstract base
 *
 * @category VuFind
 * @package  ReservationList
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
abstract class AbstractBase implements ConnectionInterface, \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;
    use GetServiceTrait;

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
     * @param array|Params        $postValues Key value pairs of post parameters to send or params plugin
     * @param UserEntityInterface $user       User entity
     * @param Form                $form       Form posted when submitting the order
     *
     * @return array [external_id: Id in external service or null, success: true or false]
     */
    public abstract function placeOrder(array|Params $postValues, UserEntityInterface $user, Form $form = null): array;

    /**
     * Check list status. Used for external services.
     *
     * @param FinnaResourceListEntityInterface $list List to check for status
     * @param UserEntityInterface              $user Current logged in user
     *
     * @return string
     */
    public abstract function getListStatus(FinnaResourceListEntityInterface $list, UserEntityInterface $user): string;

    /**
     * Initialize connection handler
     *
     * @param array $config List specific configuration from ReservationList.yaml
     *
     * @return static
     */
    public abstract function init(array $config): static;
}
