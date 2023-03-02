<?php
/**
 * Bazaar session view helper
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022-2023.
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
 * @package  View_Helpers
 * @author   Aida Luuppala <aida.luuppala@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayObject;
use Laminas\View\Helper\AbstractHelper;

/**
 * Bazaar session view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Aida Luuppala <aida.luuppala@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class BazaarSession extends AbstractHelper
{
    /**
     * Bazaar session data namespace.
     *
     * @var string
     */
    public const NAMESPACE = 'bazaar';

    /**
     * Session manager.
     *
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * Bazaar session storage container.
     *
     * @var ?ArrayObject
     */
    protected ?ArrayObject $container = null;

    /**
     * Bazaar add resource callback payload.
     *
     * @var array
     */
    protected array $payload = [];

    /**
     * Constructor
     *
     * @param SessionManager $session Session manager
     */
    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    /**
     * Return whether a Bazaar session is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $this->container = $this->session->getStorage()->offsetGet(self::NAMESPACE);
        if ($this->container) {
            return !empty($this->container['client_id']);
        }
        return false;
    }

    /**
     * Sets selection data if a Bazaar session is active.
     *
     * @param string $uid  UID
     * @param string $name Name
     *
     * @return bool Whether the data was set or not
     */
    public function setSelectionData(string $uid, string $name): bool
    {
        if (!$this->isActive()) {
            return false;
        }
        $this->payload['uid'] = $uid;
        $this->payload['name'] = $name;
        return true;
    }

    /**
     * Returns an add resource callback payload, or null if a Bazaar session is not
     * active or payload data has not been set.
     *
     * @return ?string
     */
    public function getAddResourceCallbackPayload(): ?string
    {
        if (!$this->isActive()
            || empty($this->payload['uid'])
            || empty($this->payload['name'])
        ) {
            return null;
        }
        return base64_encode(json_encode($this->payload));
    }

    /**
     * Returns the add resource callback URL, or null if a Bazaar session is not
     * active.
     *
     * @return ?string
     */
    public function getAddResourceCallbackUrl(): ?string
    {
        return $this->get('add_resource_callback_url');
    }

    /**
     * Returns the cancel URL, or null if a Bazaar session is not active.
     *
     * @return ?string
     */
    public function getCancelUrl(): ?string
    {
        return $this->get('cancel_url');
    }

    /**
     * Returns a value from Bazaar session storage container, or null if a Bazaar
     * session is not active.
     *
     * @param string $key Key
     *
     * @return mixed|null
     */
    protected function get(string $key)
    {
        if (!$this->isActive()) {
            return null;
        }
        return $this->container[$key];
    }
}
