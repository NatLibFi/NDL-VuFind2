<?php
/**
 * Custom element container block parser
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
 * Custom element container block parser
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class CustomElementContainerBlockParser implements BlockParserInterface
{
    /**
     * Names of elements that can be server-side rendered
     *
     * @var array
     */
    protected $elements;

    /**
     * Regex for matching custom element opening tags
     *
     * @var string
     */
    protected $openRegex = '/^<([A-Za-z][A-Za-z0-9]*-[A-Za-z0-9-]+)'
        . RegexHelper::PARTIAL_ATTRIBUTE . '*\s*>/';

    /**
     * CustomElementBlockParser constructor.
     *
     * @param array $elements Names of elements that can be server-side rendered
     */
    public function __construct(array $elements)
    {
        foreach ($elements as $i => $name) {
            $elements[$i] = mb_strtolower($name, 'UTF-8');
        }
        $this->elements = $elements;
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
                $name, $match, in_array($name, $this->elements)
            );
            $context->addBlock($block);

            return true;
        }

        $cursor->restoreState($savedState);

        return false;
    }
}
