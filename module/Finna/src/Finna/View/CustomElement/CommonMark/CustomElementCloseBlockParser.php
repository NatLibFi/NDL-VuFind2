<?php
/**
 * Custom element close block parser
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

/**
 * Custom element close block parser
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class CustomElementCloseBlockParser implements BlockParserInterface
{
    /**
     * Regex for matching closing tags
     *
     * @var string
     */
    protected $closeRegex = '/.*<\/([A-Za-z][A-Za-z0-9]*-[A-Za-z0-9-]+)*\s*>/';

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
