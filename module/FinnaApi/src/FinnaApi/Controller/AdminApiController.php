<?php
/**
 * Admin Api Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016-2017.
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
 * @author   Riikka Kalliomäki <riikka.kalliomaki@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace FinnaApi\Controller;

use Finna\View\Helper\Root\RecordDataFormatterFactory;

/**
 * Provides web api for different admin tasks.
 *
 * @category VuFind
 * @package  Controller
 * @author   Riikka Kalliomäki <riikka.kalliomaki@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class AdminApiController extends \VuFindApi\Controller\AdminApiController
{
    /**
     * Returns available core record fields as an associative array of
     * cssClass => translated label pairs.
     *
     * @return array
     */
    public function getRecordFieldsAction()
    {
        $this->disableSessionWrites();
        $this->determineOutputMode();

        $factory = new RecordDataFormatterFactory();
        $formatter = $factory();
        $fields = $formatter->getDefaults('core');

        $data = [];
        foreach ($fields as $key => $val) {
            if (empty($val['context']['class'])) {
                continue;
            }
            $data[] = [
                'label' => $this->translate($key),
                'class' => $val['context']['class']
            ];
        }

        return $this->output(['fields' => $data], self::STATUS_OK);
    }
}
