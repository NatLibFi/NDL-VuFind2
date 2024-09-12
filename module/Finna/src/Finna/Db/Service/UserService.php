<?php

/**
 * Database service for user.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace Finna\Db\Service;

use Finna\Db\Entity\FinnaUserCardEntityInterface;
use Finna\Db\Entity\FinnaUserEntityInterface;
use Laminas\Db\Sql\Select;
use Laminas\Session\Container as SessionContainer;
use VuFind\Crypt\HMAC;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\DbServiceAwareInterface;
use VuFind\Db\Service\DbServiceAwareTrait;
use VuFind\Db\Service\UserCardServiceInterface;

use function assert;
use function in_array;

/**
 * Database service for user.
 *
 * @category VuFind
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class UserService extends \VuFind\Db\Service\UserService implements
    DbServiceAwareInterface,
    FinnaUserServiceInterface
{
    use DbServiceAwareTrait;

    /**
     * Constructor
     *
     * @param SessionContainer $userSessionContainer Session container for user data
     * @param array            $config               Main configuration
     * @param HMAC             $hmac                 HMAC service
     */
    public function __construct(
        SessionContainer $userSessionContainer,
        protected array $config,
        protected HMAC $hmac
    ) {
        parent::__construct($userSessionContainer);
    }

    /**
     * Create an entity for the specified username.
     *
     * @param string $username Username
     *
     * @return UserEntityInterface
     */
    public function createEntityForUsername(string $username): UserEntityInterface
    {
        return parent::createEntityForUsername($this->addInstitutionPrefix($username));
    }

    /**
     * Retrieve a user object from the database based on the given field.
     * Field name must be id, username, email, verify_hash or cat_id.
     *
     * @param string          $fieldName  Field name
     * @param int|string|null $fieldValue Field value
     *
     * @return ?UserEntityInterface
     */
    public function getUserByField(string $fieldName, int|string|null $fieldValue): ?UserEntityInterface
    {
        if ('email' === $fieldName) {
            return $this->getDbTable('User')->getByEmailAndInstitutionPrefix($fieldValue);
        }
        if (in_array($fieldName, ['username', 'cat_id'])) {
            $fieldValue = $this->addInstitutionPrefix($fieldValue);
        }
        return parent::getUserByField($fieldName, $fieldValue);
    }

    /**
     * Update due date reminder setting for a user
     *
     * @param UserEntityInterface $user            User
     * @param int                 $dueDateReminder Due date reminder (days in advance)
     *
     * @return void
     */
    public function setDueDateReminderForUser(UserEntityInterface $user, int $dueDateReminder): void
    {
        assert($user instanceof FinnaUserEntityInterface);
        $user->setFinnaDueDateReminder($dueDateReminder);
        $this->persistEntity($user);
        $userCardService = $this->getDbService(UserCardServiceInterface::class);
        foreach ($userCardService->getLibraryCards($user, null, $user->getCatUsername()) as $card) {
            assert($card instanceof FinnaUserCardEntityInterface);
            $card->setFinnaDueDateReminder($dueDateReminder);
            $userCardService->persistEntity($card);
        }
    }

    /**
     * Add institution prefix to a string if it isn't already prefixed
     *
     * @param string $value String
     *
     * @return string
     */
    protected function addInstitutionPrefix(string $value): string
    {
        if ($prefix = $this->config['Site']['institution'] ?? null) {
            $prefix .= ':';
            if (!str_starts_with($value, $prefix)) {
                $value = $prefix . $value;
            }
        }
        return $value;
    }

    /**
     * Retrieve protected users.
     *
     * @return UserEntityInterface[]
     */
    public function getProtectedUsers(): array
    {
        return iterator_to_array($this->getDbTable('User')->select(['finna_protected' => 1]));
    }

    /**
     * Get users that haven't logged in since the given date.
     *
     * @param string $lastLoginDateThreshold Last login date threshold
     *
     * @return UserEntityInterface[]
     */
    public function getExpiringUsers(string $lastLoginDateThreshold): array
    {
        $listSelect = new Select('user_list');
        $listSelect->columns(['user_id']);
        $listSelect->where->equalTo('finna_protected', 1);

        $users = $this->getDbTable('User')->select(
            function (Select $select) use ($lastLoginDateThreshold, $listSelect) {
                $select->where->lessThan('last_login', $lastLoginDateThreshold);
                $select->where->notEqualTo(
                    'last_login',
                    '2000-01-01 00:00:00'
                );
                $select->where->equalTo('finna_protected', 0);
                $select->where->notIn('id', $listSelect);
            }
        );
        return iterator_to_array($users);
    }

    /**
     * Get users with due date reminders.
     *
     * @return UserEntityInterface[]
     */
    public function getUsersWithDueDateReminders(): array
    {
        $users = $this->getDbTable('User')->select(
            function (Select $select) {
                $subquery = new Select('user_card');
                $subquery->columns(['user_id']);
                $subquery->where->greaterThan('finna_due_date_reminder', 0);
                $select->where->in('id', $subquery);
                $select->order('username desc');
            }
        );
        return iterator_to_array($users);
    }

    /**
     * Check if given nickname is available
     *
     * @param string $nickname Nickname
     *
     * @return bool
     */
    public function isNicknameAvailable(string $nickname): bool
    {
        return null === $this->getDbTable('User')->select(['finna_nickname' => $nickname])->current();
    }
}
