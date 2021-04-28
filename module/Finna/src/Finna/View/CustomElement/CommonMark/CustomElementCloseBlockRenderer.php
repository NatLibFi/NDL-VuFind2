<?php
/**
 * Custom element close block renderer
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2021.
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
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Finna\View\CustomElement\CommonMark;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;

/**
 * Custom element close block renderer
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class CustomElementCloseBlockRenderer implements BlockRendererInterface
{
    /**
     * BlockRendererInterface method
     *
     * @param AbstractBlock            $block        Block element
     * @param ElementRendererInterface $htmlRenderer HTML renderer
     * @param bool                     $inTightList  Whether the element is being
     *                                               rendered in a tight list or not
     *
     * @return HtmlElement|string|null
     */
    public function render(
        AbstractBlock $block, ElementRendererInterface $htmlRenderer,
        bool $inTightList = false
    ) {
        if (!($block instanceof CustomElementCloseBlock)) {
            throw new \InvalidArgumentException(
                'Incompatible block type: ' . \get_class($block)
            );
        }

        return $block->getStringContent();
    }
}
