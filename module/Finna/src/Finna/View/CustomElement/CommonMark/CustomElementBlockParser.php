<?php
/**
 * Custom element block parser
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

use League\CommonMark\Block\Parser\BlockParserInterface;
use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;
use League\CommonMark\Util\RegexHelper;

/**
 * Custom element block parser
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class CustomElementBlockParser implements BlockParserInterface
{
    /**
     * Names of elements that can be server-side rendered
     *
     * @var array
     */
    protected $ssrElements;

    /**
     * Regex for matching custom element opening tags
     *
     * @var string
     */
    protected $openRegex = '/^<([A-Za-z][A-Za-z0-9]*-[A-Za-z0-9-]+)'
        . RegexHelper::PARTIAL_ATTRIBUTE . '*\s*>/';

    /**
     * Regex for matching custom element closing tags
     *
     * @var string
     */
    protected $closeRegex = '/<\/([A-Za-z][A-Za-z0-9]*-[A-Za-z0-9-]+)*\s*>/';

    /**
     * CustomElementBlockParser constructor.
     *
     * @param array $ssrElements Names of elements that can be server-side rendered
     */
    public function __construct(array $ssrElements)
    {
        foreach ($ssrElements as $i => $name) {
            $ssrElements[$i] = mb_strtolower($name, 'UTF-8');
        }
        $this->ssrElements = $ssrElements;
    }

    /**
     * BlockParserInterface method.
     *
     * @param ContextInterface $context Context
     * @param Cursor           $cursor  Cursor
     *
     * @return bool
     */
    public function parse(ContextInterface $context, Cursor $cursor): bool
    {
        do {
            $openingTagFound = $this->parseOpeningTag($context, $cursor);
            $closingTagFound = $this->parseClosingTag($context, $cursor);
        } while ($openingTagFound || $closingTagFound);

        return false;
    }

    /**
     * Parse opening tags.
     *
     * @param ContextInterface $context Context
     * @param Cursor           $cursor  Cursor
     *
     * @return bool
     */
    protected function parseOpeningTag(ContextInterface $context, Cursor $cursor
    ): bool {
        if ($cursor->isIndented()) {
            return false;
        }

        if ($cursor->getNextNonSpaceCharacter() !== '<') {
            return false;
        }

        $savedState = $cursor->saveState();

        $cursor->advanceToNextNonSpaceOrTab();
        $match = $cursor->match($this->openRegex);
        if (null !== $match) {
            // Do another match to get the element name.
            $matches = [];
            preg_match($this->openRegex, $match, $matches);

            $name = mb_strtolower($matches[1], 'UTF-8');
            $block = new CustomElementContainerBlock(
                $name, $match, in_array($name, $this->ssrElements)
            );
            $context->addBlock($block);

            return true;
        }

        $cursor->restoreState($savedState);

        return false;
    }

    /**
     * Parse closing tags.
     *
     * @param ContextInterface $context Context
     * @param Cursor           $cursor  Cursor
     *
     * @return bool
     */
    protected function parseClosingTag(ContextInterface $context, Cursor $cursor
    ): bool {
        if ($cursor->isIndented()) {
            return false;
        }

        $container = $context->getContainer();
        while (null !== $container
            && !($container instanceof CustomElementContainerBlock)
        ) {
            $container = $container->parent();
        }
        if (null === $container
            || !($container instanceof CustomElementContainerBlock)
        ) {
            return false;
        }

        $savedState = $cursor->saveState();

        $match = $cursor->match($this->closeRegex);
        if (null !== $match) {
            // Close possible blocks between tip and CustomElementContainerBlock.
            $tip = $context->getTip();
            while ($container !== $tip) {
                $tip->finalize($context, $context->getLineNumber());
                $tip = $context->getTip();
            }

            // Add CustomElementCloseBlock and close it.
            $closeBlock = new CustomElementCloseBlock($match);
            $context->addBlock($closeBlock);
            $closeBlock->finalize($context, $context->getLineNumber());

            // Close parent CustomElementContainerBlock.
            $context->getTip()->finalize($context, $context->getLineNumber());

            return true;
        }

        $cursor->restoreState($savedState);

        return false;
    }
}
