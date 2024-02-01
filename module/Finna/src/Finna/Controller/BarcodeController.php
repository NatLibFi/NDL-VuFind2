<?php

/**
 * Barcode Controller
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2017-2024.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Pasi Tiisanoja <pasi.tiisanoja@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Controller;

/**
 * Generates barcodes
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Pasi Tiisanoja <pasi.tiisanoja@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class BarcodeController extends \VuFind\Controller\AbstractBase
{
    /**
     * Display a barcode
     *
     * @return \Laminas\Http\Response
     */
    public function showAction()
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $code = $this->getRequest()->getQuery('code', '');
        return $this->createViewModel(compact('code'));
    }
}
