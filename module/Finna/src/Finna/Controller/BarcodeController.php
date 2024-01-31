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
     * Create a CODE 39 as SVG from a barcode string
     *
     * @param string $barcode         Barcode
     * @param int    $widthFactor     Minimum width of a single bar in user units.
     * @param int    $height          Height of barcode in user units.
     * @param string $foregroundColor Foreground color (in SVG format) for bar elements (background is transparent).
     *
     * @return string
     */
    protected function getCode39SVG(
        string $barcode,
        int $widthFactor = 2,
        int $height = 30,
        string $foregroundColor = 'black'
    ): string {
        $code39 = new \Picqer\Barcode\Types\TypeCode39();
        $barcodeData = $code39->getBarcodeData($barcode);

        // replace table for special characters
        $repstr = ["\0" => '', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;'];

        $width = round(($barcodeData->getWidth() * $widthFactor), 3);

        $svg = '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' '
        . $height . '" version="1.1" xmlns="http://www.w3.org/2000/svg">' . PHP_EOL;
        $svg .= "\t" . '<desc>' . strtr($barcodeData->getBarcode(), $repstr) . '</desc>' . PHP_EOL;
        $svg .= "\t" . '<g id="bars" fill="' . $foregroundColor . '" stroke="none">' . PHP_EOL;

        // print bars
        $positionHorizontal = 0;
        foreach ($barcodeData->getBars() as $bar) {
            $barWidth = round(($bar->getWidth() * $widthFactor), 3);
            $barHeight = round(($bar->getHeight() * $height / $barcodeData->getHeight()), 3);

            if ($bar->isBar() && $barWidth > 0) {
                $positionVertical = round(($bar->getPositionVertical() * $height / $barcodeData->getHeight()), 3);
                // draw a vertical bar
                $svg .= "\t\t" . '<rect x="' . $positionHorizontal . '" y="' . $positionVertical . '" '
                . 'width="' . $barWidth . '" height="' . $barHeight . '" />' . PHP_EOL;
            }

            $positionHorizontal += $barWidth;
        }
        $svg .= "\t</g>" . PHP_EOL;
        $svg .= '</svg>' . PHP_EOL;

        return $svg;
    }

    /**
     * Send barcode data for display in the view
     *
     * @return \Laminas\Http\Response
     */
    public function showAction()
    {
        $this->disableSessionWrites();  // avoid session write timing bug

        $code = $this->getRequest()->getQuery('code', '');

        $view = $this->createViewModel(
            [
                'code' => $code,
                'html' => $this->getCode39SVG($code, 2, 80),
            ]
        );
        $view->setTemplate('barcode/show.phtml');
        return $view;
    }
}
