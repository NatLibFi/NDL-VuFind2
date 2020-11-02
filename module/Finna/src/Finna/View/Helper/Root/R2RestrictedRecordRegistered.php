<?php
/**
 * Helper class for restricted Solr R2 records.
 *
 * Displays an indicator to REMS registered users.
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
 * Displays an indicator to REMS registered users.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class R2RestrictedRecordRegistered extends \Laminas\View\Helper\AbstractHelper
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
     * Constructor
     *
     * @param bool                   $enabled Is R2 enabled?
     * @param \Laminas\Config\Config $config  VuFind configuration
     * @param RemsService            $rems    REMS service
     * use REMS?
     */
    public function __construct(
        bool $enabled,
        \Laminas\Config\Config $config,
        RemsService $rems
    ) {
        $this->enabled = $enabled;
        $this->rems = $rems;
    }

    /**
     * Render info box.
     *
     * @param RecordDriver $driver Record driver
     * @param array        $params Parameters
     *
     * @return null|html
     */
    public function __invoke($driver, $params = null)
    {
        if (!$this->enabled) {
            return null;
        }

        // If driver is null, this is called from search results.
        if (!$driver || $driver->trymethod('hasRestrictedMetadata')) {
            $user = $params['user'] ?? null;
            try {
                if (!$user
                    || !$this->rems->hasUserAccess(true, $params['throw'] ?? false)
                ) {
                    return null;
                }
            } catch (\Exception $e) {
                $translator = $this->getView()->plugin('translate');
                return '<div class="alert alert-danger">'
                    . $translator->translate('R2_rems_connect_error') . '</div>';
            }

            $warning = null;
            if ($this->rems->isSearchLimitExceeded('daily')) {
                $warning = 'R2_daily_limit_exceeded';
            } elseif ($this->rems->isSearchLimitExceeded('monthly')) {
                $warning = 'R2_monthly_limit_exceeded';
            }
            $tplParams = [
                'usagePurpose' => $this->rems->getUsagePurpose(),
                'showInfo' => !($params['hideInfo'] ?? false),
                'warning' => $warning
            ];

            return $this->getView()->render(
                'Helpers/R2RestrictedRecordRegistered.phtml', $tplParams
            );
        }

        return null;
    }
}
