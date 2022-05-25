<?php
/**
 * AJAX handler for getting information for a field popover.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Record\Loader;
use VuFind\Session\Settings as SessionSettings;
use VuFind\View\Helper\Root\Record;
use VuFindSearch\ParamBag;

/**
 * AJAX handler for getting information for a field popover.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetFieldInfo extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Record loader
     *
     * @var Loader
     */
    protected $loader;

    /**
     * Record plugin
     *
     * @var Record
     */
    protected $recordPlugin;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss       Session settings
     * @param Loader            $loader   Record loader
     * @param Record            $rp       Record plugin
     */
    public function __construct(
        SessionSettings $ss,
        Loader $loader,
        Record $rp
    ) {
        $this->sessionSettings = $ss;
        $this->loader = $loader;
        $this->recordPlugin = $rp;
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
        $this->disableSessionWrites(); // avoid session write timing bug

        $id = $params->fromQuery('id');
        $authId = $params->fromQuery('authId');
        $source = $params->fromQuery('source');
        $recordId = $params->fromQuery('recordId');
        $type = $params->fromQuery('type');

        if (!$id || !$type) {
            return $this->formatResponse('', self::STATUS_HTTP_BAD_REQUEST);
        }

        $params = new ParamBag();
        $params->set('authorityType', $type);
        $params->set('recordSource', $source);
        $authority = null;
        if ($authId) {
            try {
                $authority = $this->loader->load(
                    $authId,
                    'SolrAuth',
                    false,
                    $params
                );
            } catch (\VuFind\Exception\RecordMissing $e) {
                return $this->formatResponse('');
            }
        }
        try {
            $driver = $this->loader->load($recordId, $source);
        } catch (\VuFind\Exception\RecordMissing $e) {
            return $this->formatResponse('');
        }

        $html = ($this->recordPlugin)($driver)->renderTemplate(
            'ajax-field-info.phtml',
            compact('id', 'authId', 'authority', 'type')
        );

        return $this->formatResponse(compact('html'));
    }
}
