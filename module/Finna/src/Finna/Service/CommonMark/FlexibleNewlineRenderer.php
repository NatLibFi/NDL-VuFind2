<?php
/**
 * Flexible newline renderer
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
 * @package  VuFind\Service
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Finna\Service\CommonMark;

use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\Newline;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;

/**
 * Flexible newline renderer
 *
 * @category VuFind
 * @package  VuFind\Service
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class FlexibleNewlineRenderer implements InlineRendererInterface
{
    /**
     * Default string for rendering soft breaks.
     *
     * @var string
     */
    public const DEFAULT_SOFT_BREAK = '<br>';

    /**
     * String to use for rendering soft breaks.
     *
     * @var string
     */
    protected $softBreak;

    /**
     * FlexibleNewlineRenderer constructor.
     */
    public function __construct()
    {
        $this->softBreak = self::DEFAULT_SOFT_BREAK;
    }

    /**
     * InlineRendererInterface method
     *
     * @param AbstractInline           $inline       Inline element
     * @param ElementRendererInterface $htmlRenderer HTML renderer
     *
     * @return HtmlElement|string|null
     */
    public function render(
        AbstractInline $inline,
        ElementRendererInterface $htmlRenderer
    ) {
        if (!($inline instanceof Newline)) {
            throw new \InvalidArgumentException(
                'Incompatible inline type: ' . \get_class($inline)
            );
        }

        if ($inline->getType() === Newline::HARDBREAK) {
            return new HtmlElement('br', [], '', true) . "\n";
        }

        return $this->softBreak;
    }

    /**
     * Set the string to use for rendering soft breaks.
     *
     * @param string $softBreak Soft break string
     *
     * @return void
     */
    public function setSoftBreak(string $softBreak): void
    {
        $this->softBreak = $softBreak;
    }
}
