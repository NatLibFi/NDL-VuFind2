<?php
/**
 * Custom element close block
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
use League\CommonMark\Block\Element\AbstractStringContainerBlock;
use League\CommonMark\Block\Element\Paragraph;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;

/**
 * Custom element close block
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class CustomElementCloseBlock extends AbstractStringContainerBlock
{
    /**
     * CustomElementCloseBlock constructor.
     *
     * @param string $contents Contents
     */
    public function __construct(string $contents)
    {
        parent::__construct();

        $this->addLine($contents);
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
        return false;
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
        return false;
    }

    /**
     * Finalize the block; mark it closed for modification
     *
     * @param ContextInterface $context       Context
     * @param int              $endLineNumber End line number
     *
     * @return void
     */
    public function finalize(ContextInterface $context, int $endLineNumber)
    {
        parent::finalize($context, $endLineNumber);

        $this->finalStringContents = implode("\n", $this->strings->toArray());
    }

    /**
     * StringContainerInterface method
     *
     * @param ContextInterface $context Context
     * @param Cursor           $cursor  Cursor
     *
     * @return void
     */
    public function handleRemainingContents(ContextInterface $context, Cursor $cursor
    ) {
        if (!$cursor->isAtEnd()) {
            // Create paragraph container for line
            $p = new Paragraph();
            $context->addBlock($p);
            $cursor->advanceToNextNonSpaceOrTab();
            $p->addLine($cursor->getRemainder());
        }
    }
}
