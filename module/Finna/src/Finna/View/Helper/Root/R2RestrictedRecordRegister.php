<?php
/**
 * Helper class for restricted Solr R2 records.
 *
 * Handles user registration to REMS.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

use Finna\Service\RemsService;

/**
 * Helper class for restricted Solr R2 records.
 *
 * Handles user registration to REMS.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class R2RestrictedRecordRegister extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Is R2 search enabled?
     *
     * @var bool
     */
    protected $enabled;

    /**
     * REMS service
     *
     * @var RemsService
     */
    protected $rems;

    /**
     * Is the user authenticated to use REMS?
     *
     * @var bool
     */
    protected $authenticated;

    /**
     * Constructor
     *
     * @param bool                   $enabled       Is R2 enabled?
     * @param \Laminas\Config\Config $config        VuFind configuration
     * @param RemsService            $rems          REMS service
     * @param bool                   $authenticated Is the user authenticated to
     * use REMS?
     */
    public function __construct(
        bool $enabled,
        \Laminas\Config\Config $config,
        RemsService $rems,
        bool $authenticated
    ) {
        $this->enabled = $enabled;
        $this->rems = $rems;
        $this->authenticated = $authenticated;
    }

    /**
     * Render info box.
     *
     * @param RecordDriver $driver Record driver
     * @param array        $params Parameters
     *
     * @return null|html
     */
    public function __invoke($driver = null, $params = null)
    {
        if (!$this->enabled) {
            return null;
        }

        // Driver is null when the helper is called outside record page
        if (!$driver || $driver->tryMethod('hasRestrictedMetadata')) {
            $user = $params['user'] ?? null;
            if ($driver ? $driver->tryMethod('isRestrictedMetadataIncluded') : false) {
                return null;
            }
            $blocklisted = $registered = $sessionClosed = false;
            $blocklistedDate = null;
            try {
                if ($this->rems->hasUserAccess(
                    $params['ignoreCache'] ?? false, true
                )
                ) {
                    // Already registered
                    return null;
                } else {
                    $blocklisted = $user ? $this->rems->isUserBlocklisted() : false;
                    if ($blocklisted) {
                        $dateTime = $this->getView()->plugin('dateTime');
                        try {
                            $blocklistedDate = $dateTime->convertToDisplayDate(
                                'Y-m-d', $blocklisted
                            );
                        } catch (\Exception $e) {
                        }
                    }
                    $status = $this->rems->getAccessPermission();
                    $sessionClosed = in_array(
                        $status,
                        [RemsService::STATUS_EXPIRED, RemsService::STATUS_REVOKED]
                    );
                }
            } catch (\Exception $e) {
                $translator = $this->getView()->plugin('translate');
                return '<div class="alert alert-danger">'
                    . $translator->translate('R2_rems_connect_error') . '</div>';
            }

            $name = '';
            if (!empty($user->firstname)) {
                $name = $user->firstname;
            }
            if (!empty($user->lastname)) {
                if (!empty($name)) {
                    $name .= ' ';
                }
                $name .= $user->lastname;
            }

            $params = [
                'warning' => $sessionClosed ? 'R2_session_expired_title' : null,
                'showInfo' => !($params['hideInfo'] ?? false),
                'weakLogin' => $user && !$this->authenticated,
                'user' => $user,
                'name' => $name,
                'id' => $driver ? $driver->getUniqueID() : null,
                'collection' => $driver ? $driver->isCollection() : false,
                'blocklisted' => $blocklisted,
                'blocklistedDate' => $blocklistedDate,
                'formId' => 'R2Register',
            ];

            return $this->getView()->render(
                'Helpers/R2RestrictedRecordPermission.phtml', $params
            );
        }

        return null;
    }
}
