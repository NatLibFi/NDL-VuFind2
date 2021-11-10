<?php
/**
 * Record field Markdown extension
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

use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Environment;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Record field Markdown extension
 *
 * @category VuFind
 * @package  VuFind\Service
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
final class RecordFieldMarkdownExtension implements ExtensionInterface
{
    /**
     * Create a new record field Markdown environment.
     *
     * @return ConfigurableEnvironmentInterface
     */
    public static function createRecordFieldMarkdownEnvironment():
        ConfigurableEnvironmentInterface
    {
        $environment = new Environment(
            [
                'html_input' => 'escape',
                'allow_unsafe_links' => false
            ]
        );
        $environment->addExtension(new static());
        return $environment;
    }

    /**
     * ExtensionInterface method.
     *
     * @param ConfigurableEnvironmentInterface $environment Environment
     *
     * @return void
     */
    public function register(ConfigurableEnvironmentInterface $environment)
    {
        $environment
            ->addBlockParser(
                new \League\CommonMark\Block\Parser\LazyParagraphParser(),
                -200
            )
            ->addInlineParser(
                new \League\CommonMark\Inline\Parser\NewlineParser(),
                200
            )
            ->addBlockRenderer(
                \League\CommonMark\Block\Element\Document::class,
                new \League\CommonMark\Block\Renderer\DocumentRenderer(),
                0
            )
            ->addBlockRenderer(
                \League\CommonMark\Block\Element\Paragraph::class,
                new \League\CommonMark\Block\Renderer\ParagraphRenderer(),
                0
            )
            ->addInlineRenderer(
                \League\CommonMark\Inline\Element\Newline::class,
                new FlexibleNewlineRenderer(),
                0
            )
            ->addInlineRenderer(
                \League\CommonMark\Inline\Element\Text::class,
                new \League\CommonMark\Inline\Renderer\TextRenderer(),
                0
            );
    }
}
