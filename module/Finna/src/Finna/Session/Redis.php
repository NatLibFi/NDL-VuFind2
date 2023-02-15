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
    use \Finna\Statistics\ReporterTrait;

    /**
     * Read function must return string value always to make save handler work as
     * expected. Return empty string if there is no data to read.
     *
     * Finna: Provides support for statistics
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
        $this->triggerStatsSessionStart((string)$sessId);
        return $result;
    }

    /**
     * Write function that is called when session data is to be saved.
     *
     * @param string $sessId The current session ID
     * @param string $data   The session data to write
     *
     * @return bool
     */
    protected function saveSession($sessId, $data): bool
    {
        try {
            return parent::saveSession($sessId, $data);
        } catch (\Exception $e) {
            // Retry once (if the connection was closed, this will re-open it):
            try {
                return parent::saveSession($sessId, $data);
            } catch (\Exception $e2) {
                // Re-throw original exception:
                throw $e;
            }
        }
    }

    /**
     * The destroy handler, this is executed when a session is destroyed with
     * session_destroy() and takes the session id as its only parameter.
     *
     * @param string $sessId The session ID to destroy
     *
     * @return bool
     */
    public function destroy($sessId): bool
    {
        try {
            parent::destroy($sessId);
        } catch (\Exception $e) {
            // Retry once (if the connection was closed, this will re-open it):
            try {
                parent::destroy($sessId);
            } catch (\Exception $e2) {
                // Re-throw original exception:
                throw $e;
            }
        }
        return true;
    }
}
