<?php

/**
 * LibraryCards view helper
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2024.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\View\Helper\Root;

use Laminas\Cache\Storage\StorageInterface as Cache;
use VuFind\Cache\CacheTrait;
use VuFind\Db\Service\UserCardServiceInterface;

use function count;

/**
 * LibraryCards view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class LibraryCards extends \VuFind\View\Helper\Root\LibraryCards
{
    use CacheTrait;
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Constructor
     *
     * @param UserCardServiceInterface $cardService User card database service
     * @param Cache                    $cache       Object cache
     */
    public function __construct(
        protected UserCardServiceInterface $cardService,
        Cache $cache
    ) {
        $this->setCacheStorage($cache);
    }

    /**
     * Get user cards as arrays
     *
     * @param \VuFind\Db\Entity\UserEntityInterface $user  User
     * @param int                                   $limit Card amount limit (optional)
     *
     * @return array
     */
    public function getCardsForUserAsArrays($user, $limit = 1): array
    {
        $cardsArray = [];
        $cards = parent::getCardsForUser($user);
        $ils = $this->getView()->plugin('ils')();
        if (count($cards) > $limit) {
            $targetCount = $ils->checkCapability('getLoginDrivers') ? count($ils->getLoginDrivers()) : 1;
            foreach ($cards as $card) {
                $card = $card->toArray();
                $target = '';
                $username = $displayUsername = $card['cat_username'];
                if (strstr($displayUsername, '.')) {
                    [$target, $displayUsername] = explode('.', $displayUsername, 2);
                }
                $display = $card['card_name'] ?: $displayUsername;
                if ($display == "$target.$displayUsername") {
                    $display = $displayUsername;
                }
                if ($target && $targetCount > 1) {
                    $display .= ' (' . $this->translate("source_$target", null, $target) . ')';
                }
                $card['display'] = $display;
                $patron = $this->getView()->plugin('auth')->getILSPatron();
                if ($patron && $patron['cat_username'] === $username) {
                    if ($barcode = $this->getCachedData($card['card_name'])) {
                        $card['barcode'] = $barcode;
                    } else {
                        $profile = $ils->getMyProfile($patron);
                        if (!empty($profile['barcode'])) {
                            if ($barcode = $profile['barcode']) {
                                $this->putCachedData($card['card_name'], $barcode);
                            }
                        }
                    }
                }
                $card['selected_card'] = strcasecmp($username, $user->getCatUsername() ?? '') === 0;
                $cardsArray[] = $card;
            }
        }
        return $cardsArray;
    }
}
