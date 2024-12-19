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

use VuFind\Db\Service\UserCardServiceInterface;

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
     *
     * @deprecated Use displayBarcodeAction instead
     */
    public function showAction()
    {
        try {
            if (!($user = $this->getUser())) {
                return $this->forceLogin();
            }
            $code = $this->getRequest()->getQuery('code', '');
            $cards = $this->getDbService(UserCardServiceInterface::class)->getLibraryCards($user, null);
            foreach ($cards as $card) {
                $username = $card->getCatUsername();
                if (str_contains($username, '.')) {
                    [, $username] = explode('.', $username, 2);
                }
                if ($username === $code) {
                    return $this->redirect()->toRoute('librarycards-displaybarcode', ['id' => $card->getId()]);
                }
            }
            $catalog = $this->getILS();
            $auth = $this->getILSAuthenticator();
            foreach ($cards as $card) {
                if ($card->getCatUsername() === $user->getCatUsername()) {
                    $patron = $auth->storedCatalogLogin();
                } else {
                    $loginUser = clone $user;
                    $loginUser->setCatUsername($card->getCatUsername());
                    $loginUser->setRawCatPassword($card->getRawCatPassword());
                    $loginUser->setCatPassEnc($card->getCatPassEnc());
                    $patron = $catalog->patronLogin(
                        $loginUser->getCatUsername(),
                        $auth->getCatPasswordForUser($loginUser)
                    );
                }
                $profile = $catalog->getMyProfile($patron);
                if (!empty($profile['barcode']) && $code === $profile['barcode']) {
                    return $this->redirect()->toRoute('librarycards-displaybarcode', ['id' => $card->getId()]);
                }
            }
            throw new \Exception();
        } catch (\Exception) {
            $this->flashMessenger()->addErrorMessage('An error has occurred');
            return $this->redirect()->toRoute('librarycards-home');
        }
    }
}
