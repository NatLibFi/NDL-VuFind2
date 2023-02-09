<?php
/**
 * Redis session handler extensions
 *
 * PHP version 7
 *
 * Coypright (C) The National Library of Finland 2023.
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
 * @package  Session_Handlers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:session_handlers Wiki
 */
namespace Finna\Session;

use Laminas\Config\Config;

/**
 * Redis session handler extensions
 *
 * @category VuFind
 * @package  Session_Handlers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:session_handlers Wiki
 */
class Redis extends \VuFind\Session\Redis
{
    /**
     * Whether to try to find an existing session from the database. Used while
     * migrating to Redis to avoid lost sessions.
     *
     * @var bool
     */
    protected $readFromDatabase;

    /**
     * Constructor
     *
     * @param \Credis_Client $connection Redis connection object
     * @param Config         $config     Session configuration ([Session] section of
     * config.ini)
     */
    public function __construct(\Credis_Client $connection, Config $config = null)
    {
        parent::__construct($connection, $config);
        $this->readFromDatabase
            = (bool)($config->redis_read_sessions_from_db ?? false);
    }

    /**
     * Read function must return string value always to make save handler work as
     * expected. Return empty string if there is no data to read.
     *
     * Finna: Provides support for a secondary read-only Database storage to avoid
     * lost sessions during migration.
     *
     * @param string $sessId The session ID to read
     *
     * @return string
     */
    public function read($sessId): string
    {
        if ($result = parent::read($sessId)) {
            return $result;
        }
        if ($this->readFromDatabase) {
            // Adapted from Database driver:

            // Try to read the session, but destroy it if it has expired:
            $sessionTable = $this->getTable('Session');
            try {
                return $sessionTable->readSession($sessId, $this->lifetime);
            } catch (\VuFind\Exception\SessionExpired $e) {
                $sessionTable->destroySession($sessId);
                return '';
            }
        }
        return '';
    }
}
