<?php

/**
 * Database service for UserCard.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use Closure;
use Doctrine\ORM\EntityManager;
use Finna\Db\Entity\UserCard;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Config\AccountCapabilities;
use VuFind\Db\Entity\PluginManager as EntityPluginManager;
use VuFind\Db\Entity\UserCardEntityInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\PersistenceManager;

use function assert;
use function in_array;
use function is_int;

/**
 * Database service for UserCard.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class UserCardService extends \VuFind\Db\Service\UserCardService implements UserCardServiceInterface
{
    /**
     * Constructor.
     *
     * @param EntityManager       $entityManager          Doctrine ORM entity manager
     * @param EntityPluginManager $entityPluginManager    VuFind entity plugin manager
     * @param PersistenceManager  $persistenceManager     Entity persistence manager
     * @param ILSAuthenticator    $ilsAuthenticator       ILS authenticator
     * @param AccountCapabilities $capabilities           Account capabilities configuration
     * @param Closure             $getLoginTargetPrefixes Callback for getting a list of active login target prefixes
     */
    public function __construct(
        EntityManager $entityManager,
        EntityPluginManager $entityPluginManager,
        PersistenceManager $persistenceManager,
        ILSAuthenticator $ilsAuthenticator,
        AccountCapabilities $capabilities,
        protected Closure $getLoginTargetPrefixes
    ) {
        parent::__construct(
            $entityManager,
            $entityPluginManager,
            $persistenceManager,
            $ilsAuthenticator,
            $capabilities
        );
    }

    /**
     * Get all library cards associated with the user.
     *
     * Finna: filters out cards for inactive login targets.
     *
     * @param UserEntityInterface|int $userOrId    User object or identifier
     * @param ?int                    $id          Optional card ID filter
     * @param ?string                 $catUsername Optional catalog username filter
     *
     * @return UserCardEntityInterface[]
     */
    public function getLibraryCards(
        UserEntityInterface|int $userOrId,
        ?int $id = null,
        ?string $catUsername = null
    ): array {
        $cards = parent::getLibraryCards($userOrId, $id, $catUsername);
        // Filter cards by active login targets unless a specific subset of cards was requested:
        if ($cards && null === $id && null === $catUsername) {
            $prefixes = ($this->getLoginTargetPrefixes)();
            $cards = array_filter(
                $cards,
                function ($card) use ($prefixes) {
                    [$catPrefix] = explode('.', $card->getCatUsername());
                    return in_array("$catPrefix.", $prefixes);
                }
            );
        }
        return $cards;
    }

    /**
     * Get all users associated with a library card.
     *
     * @param string $catUsername Catalog username
     *
     * @return array
     */
    public function getConnectedAccountInfoForLibraryCard(string $catUsername): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('uc')
            ->from(UserCard::class, 'uc')
            ->where('uc.catUsername = :catUsername')
            ->setParameter('catUsername', $catUsername);

        $result = $qb->getQuery()->getResult();
        return $result;
    }

    /**
     * Get all library cards associated with the user.
     *
     * @param UserEntityInterface|int $userOrId    User object or identifier
     * @param ?int                    $id          Optional card ID filter
     * @param ?string                 $catUsername Optional catalog username filter
     *
     * @return UserCardEntityInterface[]
     */
    public function getAllLibraryCards(
        UserEntityInterface|int $userOrId,
        ?int $id = null,
        ?string $catUsername = null
    ): array {
        return parent::getLibraryCards($userOrId, $id, $catUsername);
    }

    /**
     * Verify that the user's current ILS settings exist in their library card data
     * (if enabled) and are up to date. Designed to be called after updating the
     * user row; will create or modify library card rows as needed.
     *
     * @param UserEntityInterface|int $userOrId User object or identifier
     *
     * @return bool
     * @throws \VuFind\Exception\PasswordSecurity
     */
    public function synchronizeUserLibraryCardData(UserEntityInterface|int $userOrId): bool
    {
        parent::synchronizeUserLibraryCardData($userOrId);

        // Synchronize due date reminder setting
        if (!$this->capabilities->libraryCardsEnabled()) {
            return true; // success, because there's nothing to do
        }
        $user = is_int($userOrId)
            ? $this->getDbService(UserServiceInterface::class)->getUserById($userOrId) : $userOrId;
        assert($user instanceof \Finna\Db\Entity\UserEntityInterface);
        if (!$user->getCatUsername()) {
            return true; // success, because there's nothing to do
        }
        $cards = $this->getLibraryCards($user, catUsername: $user->getCatUsername());
        if (!($card = reset($cards))) {
            // This should never happen!
            return true;
        }
        $card->setFinnaDueDateReminder($user->getFinnaDueDateReminder());

        $this->persistEntity($card);
        return true;
    }
}
