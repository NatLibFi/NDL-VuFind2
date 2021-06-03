<?php
/**
 * Custom element container block renderer
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

use Exception;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;

/**
 * Custom element container block renderer
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class CustomElementContainerBlockRenderer extends CustomElementRendererBase
    implements BlockRendererInterface
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
        if (!($block instanceof CustomElementContainerBlock)) {
            throw new \InvalidArgumentException(
                'Incompatible block type: ' . \get_class($block)
            );
        }

        $children = $block->children();
        $stringContent = $block->getOpenTag() . "\n"
            . $htmlRenderer->renderBlocks($children);

        if ($block->shouldSsr()) {
            try {
                $stringContent = $this->customElementRenderer->render(
                    $block->getName(), ['outerHTML' => $stringContent]
                );
            } catch (Exception $e) {
                // If server-side rendering fails for some reason, just return the
                // element as is.
            }
        }

        return $stringContent;
    }
}
