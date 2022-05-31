<?php
/**
 * Finna record field Markdown service
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
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\Service;

use Finna\Service\CommonMark\FlexibleNewlineRenderer;
use League\CommonMark\Inline\Element\Newline;
use League\CommonMark\MarkdownConverter;

/**
 * Finna record field Markdown service
 *
 * @category VuFind
 * @package  VuFind\Service
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class RecordFieldMarkdown extends MarkdownConverter
{
    /**
     * Flexible newline renderer.
     *
     * @var FlexibleNewlineRenderer
     */
    protected $flexibleNewlineRenderer = null;

    /**
     * Converts Markdown to HTML.
     *
     * @param string  $markdown  Markdown
     * @param ?string $softBreak Alternative string to use for rendering soft breaks
     *                           (optional)
     *
     * @return string
     */
    public function convertToHtml(string $markdown, ?string $softBreak = null)
        : string
    {
        if (isset($softBreak) && !$this->flexibleNewlineRenderer) {
            $renderers = $this->getEnvironment()
                ->getInlineRenderersForClass(Newline::class);
            foreach ($renderers as $renderer) {
                if ($renderer instanceof FlexibleNewlineRenderer) {
                    $this->flexibleNewlineRenderer = $renderer;
                    break;
                }
            }
        }
        $isNonDefault
            = isset($softBreak)
                && $this->flexibleNewlineRenderer
                && ($softBreak !== FlexibleNewlineRenderer::DEFAULT_SOFT_BREAK);
        if ($isNonDefault) {
            $this->flexibleNewlineRenderer->setSoftBreak($softBreak);
        }
        $html = parent::convertToHtml($markdown);
        if ($isNonDefault) {
            $this->flexibleNewlineRenderer->setSoftBreak(
                FlexibleNewlineRenderer::DEFAULT_SOFT_BREAK
            );
        }
        return $html;
    }
}
