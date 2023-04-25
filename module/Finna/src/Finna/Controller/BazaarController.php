<?php

/**
 * Bazaar controller.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2023.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Controller;

use Finna\Service\BazaarService;
use Laminas\Http\Response;

/**
 * Bazaar controller.
 *
 * @category VuFind
 * @package  Controller
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class BazaarController extends \VuFind\Controller\AbstractBase
{
    /**
     * Attempt to create a Bazaar session if needed.
     *
     * @return Response
     */
    public function homeAction(): Response
    {
        $bazaarService = $this->serviceLocator->get(BazaarService::class);
        if (!$bazaarService->isSessionActive()) {
            $bazaarService->createSession($this->getRequest()->getQuery('hash'));
        }
        return $this->redirect()->toRoute('search-home');
    }

    /**
     * Destroys a Bazaar session if one exists, then redirects to the cancel URL if
     * it was set, otherwise redirects to search home.
     *
     * @return Response
     */
    public function cancelAction(): Response
    {
        $bazaarService = $this->serviceLocator->get(BazaarService::class);
        $redirectUrl = $bazaarService->getCancelUrl()
            ?: $this->url()->fromRoute('search-home');
        $bazaarService->destroySession();
        return $this->redirect()->toUrl($redirectUrl);
    }
}
