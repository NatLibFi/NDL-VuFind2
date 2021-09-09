<?php
/**
 * Custom element container block
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
use League\CommonMark\Cursor;

/**
 * Custom element container block
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class CustomElementContainerBlock extends AbstractBlock
{
    /**
     * Custom element name
     *
     * @var string
     */
    protected $name;

    /**
     * Open tag
     *
     * @var string
     */
    protected $openTag;

    /**
     * Can the custom element be server-side rendered
     *
     * @var bool
     */
    protected $canSsr;

    /**
     * CustomElementBlock constructor.
     *
     * @param string $name    Custom element name
     * @param string $openTag Matched open tag
     * @param bool   $canSsr  Can the element be server-side rendered
     */
    public function __construct(string $name, string $openTag, bool $canSsr)
    {
        $this->name = $name;
        $this->openTag = $openTag;
        $this->canSsr = $canSsr;
    }

    /**
     * Get custom element name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get block open tag
     *
     * @return string
     */
    public function getOpenTag(): string
    {
        return $this->openTag;
    }

    /**
     * Should the custom element be server-side rendered
     *
     * @return bool
     */
    public function shouldSsr():  bool
    {
        return $this->canSsr
            && false === strpos($this->openTag, 'ssr="false"');
    }

    /**
     * Returns true if this block can contain the given block as a child node
     *
     * @param AbstractBlock $block Block
     *
     * @return bool
     */
    public function canContain(AbstractBlock $block): bool
    {
        return true;
    }

    /**
     * Whether this is a code block
     *
     * Code blocks are extra-greedy - they'll try to consume all subsequent
     * lines of content without calling matchesNextLine() each time.
     *
     * @return bool
     */
    public function isCode(): bool
    {
        return false;
    }

    /**
     * AbstractBlock method
     *
     * @param Cursor $cursor Cursor
     *
     * @return bool
     */
    public function matchesNextLine(Cursor $cursor): bool
    {
        // The block remains open until closed by a child CustomElementCloseBlock.
        return true;
    }
}
