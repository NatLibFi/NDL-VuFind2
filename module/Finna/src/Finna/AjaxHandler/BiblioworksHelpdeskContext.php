<?php

/**
 * Biblioworks Helpdesk Context AJAX Handler
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
 * @package  AJAX
 * @author   Biblioworks.ai <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */

namespace Finna\AjaxHandler;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Session\SessionManager;
use Psr\Log\LoggerAwareInterface;
use VuFind\Auth\ILSAuthenticator;
use VuFind\Auth\Manager as AuthManager;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\UserCardServiceInterface;
use VuFind\ILS\Connection as ILSConnection;
use VuFind\Log\LoggerAwareTrait;
use VuFind\Session\Settings as SessionSettings;

/**
 * Biblioworks Helpdesk Context AJAX Handler
 *
 * Mints UST (User Session Token) for authenticated users.
 * UST is an encrypted token containing patron_id, used by the helpdesk adapter
 * to securely access patron (loan) data.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Biblioworks.ai <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */
class BiblioworksHelpdeskContext extends \VuFind\AjaxHandler\AbstractBase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Constructor
     *
     * @param SessionSettings          $sessionSettings   Session settings
     * @param SessionManager           $sessionManager    Session manager
     * @param AuthManager              $authManager       Auth manager
     * @param ILSAuthenticator         $ilsAuthenticator  ILS authenticator
     * @param ILSConnection            $ils               ILS connection
     * @param array                    $biblioworksConfig Biblioworks configuration
     * @param UserCardServiceInterface $userCardService   User card service
     */
    public function __construct(
        SessionSettings $sessionSettings,
        protected SessionManager $sessionManager,
        protected AuthManager $authManager,
        protected ILSAuthenticator $ilsAuthenticator,
        protected ILSConnection $ils,
        protected array $biblioworksConfig,
        protected UserCardServiceInterface $userCardService
    ) {
        $this->sessionSettings = $sessionSettings;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();

        $settings = $this->getIntegrationSettings();

        $enabledRaw = $settings['enabled'] ?? false;
        $enabled = filter_var(
            $enabledRaw,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        if ($enabled === null) {
            $enabled = (bool)$enabledRaw;
        }
        if (!$enabled) {
            return $this->formatResponse(['logged_in' => false]);
        }

        // 1. Check if user is logged in
        $user = $this->authManager->getUserObject();
        if (!$user) {
            return $this->formatResponse(['logged_in' => false]);
        }

        // 2. Resolve current catalog session
        $patron = $this->ilsAuthenticator->storedCatalogLogin();
        if (!is_array($patron)) {
            $patron = $this->attemptLibraryCardLogin($user);
        }
        if (!is_array($patron) && $this->allowMockPatron($settings)) {
            $patron = $this->createMockPatronFromUser($user);
        }
        if (!is_array($patron)) {
            return $this->formatResponse(['logged_in' => false]);
        }

        $patronId = $this->extractPatronId($patron);
        if ($patronId === null) {
            return $this->formatResponse(['logged_in' => false]);
        }

        $sessionId = $this->resolveSessionId();
        if ($sessionId === null) {
            $this->logError('No active Finna session id; refusing to mint UST.');
            return $this->formatResponse(['logged_in' => false]);
        }

        // 3. Build UST payload
        $now = time();
        // Default TTL aligns with the config template (2 days) but can be overridden
        $ttl = (int)($settings['ust_ttl_seconds'] ?? 172800);
        if ($ttl <= 0) {
            $ttl = 172800;
        }
        $payload = [
            'sub' => (string)$patronId,       // Patron/borrower ID
            'iat' => $now,
            'exp' => $now + $ttl,
            'iss' => (string)($settings['ust_issuer'] ?? 'example.finna.fi'),
            'aud' => (string)($settings['ust_audience'] ?? 'biblioworks-adapter'),
            'sid' => $sessionId,
        ];

        // 4. Encrypt as UST (opaque to frontend)
        try {
            $ust = $this->encryptUST($payload, $settings);
        } catch (\Exception $e) {
            $this->logError('UST encryption failed - ' . $e->getMessage());
            return $this->formatResponse([
                'logged_in' => false,
                'error' => 'Token generation failed',
            ], 500);
        }

        // 5. Return to frontend
        // Note: patron_id is NOT exposed for privacy (encrypted inside UST)
        return $this->formatResponse([
            'logged_in' => true,
            'ust' => $ust,
            'expires_at' => $payload['exp'],
        ]);
    }

    /**
     * Encrypt UST payload using Defuse Crypto
     *
     * @param array $payload  JWT-like payload to encrypt
     * @param array $settings Integration settings
     *
     * @return string Encrypted UST (base64 authenticated ciphertext)
     *
     * @throws \Exception If encryption fails or key is invalid
     */
    protected function encryptUST(array $payload, array $settings): string
    {
        $keyString = (string)($settings['ust_encryption_key'] ?? '');
        if ($keyString === '') {
            throw new \Exception('UST encryption key not configured');
        }

        // Load Defuse encryption key
        $key = Key::loadFromAsciiSafeString($keyString);
        $serializedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($serializedPayload === false) {
            throw new \Exception('Failed to encode UST payload');
        }

        // Encrypt payload (returns authenticated ciphertext)
        // Security: AES-256-CTR + HMAC-SHA256 (encrypt-then-MAC)
        return Crypto::encrypt($serializedPayload, $key);
    }

    /**
     * Fetch integration configuration
     *
     * @return array
     */
    protected function getIntegrationSettings(): array
    {
        return $this->biblioworksConfig['BiblioworksHelpdesk'] ?? [];
    }

    /**
     * Extract the canonical patron identifier from catalog session details.
     *
     * @param array $patron Patron session data
     *
     * @return ?string
     */
    protected function extractPatronId(array $patron): ?string
    {
        if (!empty($patron['id'])) {
            return (string)$patron['id'];
        }

        if ($this->ils->checkCapability('getMyProfile', compact('patron'))) {
            try {
                $profile = $this->ils->getMyProfile($patron);
                if (!empty($profile['id'])) {
                    return (string)$profile['id'];
                }
                if (!empty($profile['full_data']['borrowernumber'])) {
                    return (string)$profile['full_data']['borrowernumber'];
                }
            } catch (\Throwable $e) {
                $this->logError('getMyProfile failed - ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Attempt to initialize catalog login via stored library cards.
     *
     * @param UserEntityInterface $user Logged in user
     *
     * @return ?array
     */
    protected function attemptLibraryCardLogin(UserEntityInterface $user): ?array
    {
        try {
            $currentCatUsername = $user->getCatUsername();
            $cards = $this->userCardService->getLibraryCards($user);
            if (!$cards) {
                return null;
            }

            // Only auto-activate when no active card is set or the active card is missing.
            $needsActivation = empty($currentCatUsername)
                || !array_filter(
                    $cards,
                    function ($card) use ($currentCatUsername) {
                        return $card->getCatUsername() === $currentCatUsername;
                    }
                );

            if (!$needsActivation) {
                return null;
            }

            if (count($cards) > 1) {
                // Ambiguous which card to activate automatically. Multiple cards for an account is not in current use?!
                return null;
            }

            $card = current($cards);
            if (!$card || $card->getId() === null) {
                return null;
            }

            $this->userCardService->activateLibraryCard($user, $card->getId());
            $this->authManager->updateSession($user);
            return $this->ilsAuthenticator->storedCatalogLogin() ?: null;
        } catch (\Throwable $e) {
            $this->logError('Library card activation failed - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Determine if mock patron mode is allowed.
     *
     * @param array $settings Integration settings
     *
     * @return bool
     */
    protected function allowMockPatron(array $settings): bool
    {
        $flag = $settings['allow_mock_patron'] ?? false;
        $bool = filter_var($flag, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
            $bool = (bool)$flag;
        }
        return $bool;
    }

    /**
     * Build a mock patron array using the currently logged-in user record.
     *
     * @param UserEntityInterface $user Logged in user
     *
     * @return ?array
     */
    protected function createMockPatronFromUser(UserEntityInterface $user): ?array
    {
        $catalogId = $user->getCatId();
        $catalogUsername = $user->getCatUsername();
        $internalId = $user->getId();

        if (empty($catalogId) && empty($catalogUsername) && $internalId === null) {
            return null;
        }

        $id = $catalogId ?: (string)$internalId;

        return [
            'id' => (string)$id,
            'cat_username' => $catalogUsername ?: (string)$internalId,
            'mock' => true,
        ];
    }

    /**
     * Resolve the currently active Finna session identifier.
     *
     * @return ?string
     */
    protected function resolveSessionId(): ?string
    {
        $sid = session_id();
        if (is_string($sid) && $sid !== '') {
            return $sid;
        }

        try {
            $sidFromManager = $this->sessionManager->getId();
            if (is_string($sidFromManager) && $sidFromManager !== '') {
                return $sidFromManager;
            }
        } catch (\Throwable $e) {
            $this->logError(
                'Failed to resolve session id via manager - '
                . $e->getMessage()
            );
        }

        return null;
    }
}
