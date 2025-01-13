<?php

/**
 * Ajax Controller Module
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2018.
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
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */

namespace Finna\Controller;

/**
 * This controller handles Finna AJAX functionality
 *
 * @category VuFind
 * @package  Controller
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class AjaxController extends \VuFind\Controller\AjaxController
{
    /**
     * Handle online payment notification callback.
     *
     * An empty response with HTTP code 200 is returned
     *
     * @return \Laminas\Http\Response
     */
    public function onlinePaymentNotifyAction()
    {
        // Use text/html to avoid any output
        return $this->callAjaxMethod('onlinePaymentNotify', 'text/html');
    }

    /**
     * Format the content of the AJAX response based on the response type.
     *
     * @param string $type     Content-type of output
     * @param mixed  $data     The response data
     * @param int    $httpCode A custom HTTP Status Code
     *
     * @return string
     * @throws \Exception
     */
    protected function formatContent($type, $data, $httpCode)
    {
        if ($type !== 'file_type_content') {
            return parent::formatContent($type, $data, $httpCode);
        }
        if ($httpCode === 200) {
            return $this->getFileResponse($data);
        } else {
            return parent::formatContent('text/plain', $data, $httpCode);
        }
    }

    /**
     * Get a file download
     *
     * @return \Laminas\Http\Response
     */
    public function fileAction()
    {
        $method = $this->params()->fromQuery('method');
        if (!$method) {
            return $this->getAjaxResponse('text/plain', ['error' => 'Parameter "method" missing'], 400);
        }
        return $this->callAjaxMethod($method, 'file_type_content');
    }

    /**
     * Send output data and exit.
     *
     * @param mixed $data The response data
     *
     * @return \Laminas\Http\Response
     * @throws \Exception
     */
    protected function getFileResponse($data)
    {
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-type', $data['mediaType']);
        $headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . $data['fileName'] . '"');
        return stream_get_contents($data['filePointer']);
    }
}
