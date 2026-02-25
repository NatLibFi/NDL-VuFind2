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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
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
use VuFind\ILS\Connection as ILSConnection;
use VuFind\Log\LoggerAwareTrait;
use VuFind\Session\Settings as SessionSettings;

use function is_array;

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
     * @param SessionSettings  $sessionSettings   Session settings
     * @param SessionManager   $sessionManager    Session manager
     * @param AuthManager      $authManager       Auth manager
     * @param ILSAuthenticator $ilsAuthenticator  ILS authenticator
     * @param ILSConnection    $ils               ILS connection
     * @param array            $biblioworksConfig Biblioworks configuration
     */
    public function __construct(
        SessionSettings $sessionSettings,
        protected SessionManager $sessionManager,
        protected AuthManager $authManager,
        protected ILSAuthenticator $ilsAuthenticator,
        protected ILSConnection $ils,
        protected array $biblioworksConfig
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

        $enabled = (bool)($settings['enabled'] ?? false);
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
        if (!is_array($patron) && $this->allowMockPatron()) {
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

        $issuer = (string)($settings['ust_issuer'] ?? '');
        if ($issuer === '') {
            $this->logError('ust_issuer not configured; refusing to mint UST.');
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
            'sub' => $patronId,       // Patron/borrower ID
            'iat' => $now,
            'exp' => $now + $ttl,
            'iss' => $issuer,
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
        if ($id = $patron['id'] ?? null) {
            return (string)$id;
        }

        if ($this->ils->checkCapability('getMyProfile', compact('patron'))) {
            try {
                $profile = $this->ils->getMyProfile($patron);
                if ($id = $profile['id'] ?? null) {
                    return (string)$id;
                }
                if ($id = $profile['full_data']['borrowernumber'] ?? null) {
                    return (string)$id;
                }
            } catch (\Throwable $e) {
                $this->logError('getMyProfile failed - ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Determine if mock patron mode is allowed.
     *
     * @return bool
     */
    protected function allowMockPatron(): bool
    {
        $settings = $this->getIntegrationSettings();
        return (bool)($settings['allow_mock_patron'] ?? false);
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
            'id' => $id,
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
        return $this->sessionManager->getId() ?: null;
    }
}
