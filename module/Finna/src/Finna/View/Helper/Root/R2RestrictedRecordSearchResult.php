<?php
/**
 * Helper class for displaying an indicator for restricted
 * Solr R2 record in search results.
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

/**
 * Helper class for displaying an indicator for restricted
 * Solr R2 record in search results.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class R2RestrictedRecordSearchResult extends \Laminas\View\Helper\AbstractHelper
    implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Is R2 search enabled?
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Is user allowed to see restricted metadata?
     *
     * @var bool
     */
    protected $hasAccess;

    /**
     * Constructor
     *
     * @param bool $enabled   Is R2 enabled?
     * @param bool $hasAccess Is user allowed to see restricted metadata?
     */
    public function __construct(bool $enabled, bool $hasAccess)
    {
        $this->enabled = $enabled;
        $this->hasAccess = $hasAccess;
    }

    /**
     * Render info box.
     *
     * @param RecordDriver $driver Record driver
     * @param string       $type   icon|info
     *
     * @return null|html
     */
    public function __invoke($driver, $type = 'icon')
    {
        if (!$this->enabled
            || !$driver->tryMethod('hasRestrictedMetadata', [], false)
        ) {
            return null;
        }

        if ($type === 'icon') {
            // Icon overlayed on record image
            return $this->getView()->render(
                'Helpers/R2RestrictedRecordSearchResult.phtml',
                ['hasAccess' => $this->hasAccess]
            );
        } else {
            // Info text in content area
            return '<div class="r2-result-restricted-info alert alert-success">'
                . $this->translator->translate(
                    'R2_restricted_record_note_searchresult'
                )
                . '</div>';
        }
    }
}
