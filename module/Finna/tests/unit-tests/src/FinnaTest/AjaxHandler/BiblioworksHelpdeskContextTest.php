<?php

/**
 * BiblioworksHelpdeskContext test class.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * @package  Tests
 * @author   BiblioWorks <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */

namespace FinnaTest\AjaxHandler;

use Finna\AjaxHandler\BiblioworksHelpdeskContext;
use Laminas\Session\SessionManager;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\UserCardServiceInterface;
use VuFind\ILS\Connection as ILSConnection;
use VuFind\Session\Settings as SessionSettings;

/**
 * BiblioworksHelpdeskContext test class.
 *
 * @category VuFind
 * @package  Tests
 * @author   BiblioWorks <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */
class BiblioworksHelpdeskContextTest extends \VuFindTest\Unit\AjaxHandlerTestCase
{
    /**
     * Build handler with provided config and user.
     *
     * @param array                $config Biblioworks config
     * @param ?UserEntityInterface $user   Logged in user (or null)
     *
     * @return BiblioworksHelpdeskContext
     */
    protected function getHandler(
        array $config,
        ?UserEntityInterface $user = null
    ): BiblioworksHelpdeskContext {
        $sessionSettings = $this->container->createMock(
            SessionSettings::class,
            ['disableWrite']
        );
        $sessionSettings->expects($this->once())->method('disableWrite');
        $sessionManager = $this->container->createMock(SessionManager::class, ['getId']);
        $authManager = $this->getMockAuthManager($user);
        $ilsAuthenticator = $this->container->createMock(ILSAuthenticator::class);
        $ils = $this->container->createMock(ILSConnection::class);
        $userCardService = $this->container->createMock(UserCardServiceInterface::class);

        return new BiblioworksHelpdeskContext(
            $sessionSettings,
            $sessionManager,
            $authManager,
            $ilsAuthenticator,
            $ils,
            $config,
            $userCardService
        );
    }

    /**
     * Test response when integration is disabled.
     *
     * @return void
     */
    public function testReturnsLoggedOutWhenDisabled(): void
    {
        $config = [
            'BiblioworksHelpdesk' => [
                'enabled' => false,
            ],
        ];
        $handler = $this->getHandler($config, $this->getMockUser());
        $params = $this->getParamsHelper();
        $this->assertEquals(
            [['logged_in' => false]],
            $handler->handleRequest($params)
        );
    }

    /**
     * Test response when user is not authenticated.
     *
     * @return void
     */
    public function testReturnsLoggedOutWhenNotAuthenticated(): void
    {
        $config = [
            'BiblioworksHelpdesk' => [
                'enabled' => true,
            ],
        ];
        $handler = $this->getHandler($config, null);
        $params = $this->getParamsHelper();
        $this->assertEquals(
            [['logged_in' => false]],
            $handler->handleRequest($params)
        );
    }

    /**
     * Test successful response using mock patron mode.
     *
     * @return void
     */
    public function testReturnsUstWhenMockPatronEnabled(): void
    {
        $key = \Defuse\Crypto\Key::createNewRandomKey()->saveToAsciiSafeString();
        $config = [
            'BiblioworksHelpdesk' => [
                'enabled' => true,
                'allow_mock_patron' => true,
                'ust_encryption_key' => $key,
            ],
        ];

        $user = $this->getMockUser();
        $user->expects($this->any())->method('getCatId')->willReturn('borrower-123');
        $user->expects($this->any())->method('getCatUsername')->willReturn('borrower-123');
        $user->expects($this->any())->method('getId')->willReturn(123);

        $handler = $this->getHandler($config, $user);
        $params = $this->getParamsHelper();

        $originalSid = session_id();
        $sessionActive = session_status() === PHP_SESSION_ACTIVE;
        $changedSid = false;
        if (!$sessionActive && $originalSid === '') {
            session_id('testsid');
            $changedSid = true;
        }
        $result = $handler->handleRequest($params);
        if ($changedSid) {
            session_id($originalSid);
        }

        $this->assertIsArray($result);
        $this->assertSame(1, count($result));
        $data = $result[0];
        $this->assertSame(true, $data['logged_in']);
        $this->assertNotEmpty($data['ust']);
        $this->assertIsInt($data['expires_at']);
    }
}
