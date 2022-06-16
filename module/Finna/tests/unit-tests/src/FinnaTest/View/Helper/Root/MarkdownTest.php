<?php

/**
 * Markdown Test Class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  Tests
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
namespace FinnaTest\View\Helper\Root;

use Finna\CommonMark\Extension\CustomElementExtension;
use Finna\View\CustomElement\FinnaPanel;
use Finna\View\CustomElement\FinnaTruncate;
use Finna\View\CustomElement\PluginManager;
use Finna\View\Helper\Root\AdjustHeadingLevel;
use Finna\View\Helper\Root\CleanHtml;
use Finna\View\Helper\Root\CleanHtmlFactory;
use Finna\View\Helper\Root\CustomElement;
use Finna\View\Helper\Root\Markdown;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Markdown Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class MarkdownTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\ViewTrait;

    protected Markdown $helper;

    /**
     * Get view helper to test.
     *
     * @return Markdown
     */
    protected function getHelper()
    {
        if (isset($this->helper)) {
            return $this->helper;
        }

        $elements = [
            'finna-panel' => FinnaPanel::class,
            'finna-truncate' => FinnaTruncate::class,
        ];

        $view = $this->getPhpRenderer(
            [
                'adjustHeadingLevel' => new AdjustHeadingLevel(),
                'cleanHtml' => new CleanHtml(
                    null,
                    CleanHtmlFactory::getAllowedElements($elements)
                ),
            ],
            'finna2'
        );

        // Create Markdown environment.
        $environment = new Environment([
            'html_input' => 'allow',
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager
            ->method('get')
            ->willReturnCallback(function ($name, $options) use ($elements) {
                return new $elements[$name]($options['__element'], $options);
            });
        $customElementHelper = new CustomElement($pluginManager);
        $customElementHelper->setView($view);
        $environment->addExtension(
            new CustomElementExtension(array_keys($elements), $customElementHelper)
        );

        // Create markdown helper.
        $markdown = new Markdown(new MarkdownConverter($environment));
        $markdown->setView($view);
        $this->helper = $markdown;

        return $markdown;
    }

    /**
     * Test default heading level adjustment of +1.
     *
     * @return void
     */
    public function testDefaultHeadingLevelAdjustment()
    {
        $markdown = "# One\n## Two\n### Three\n#### Four\n##### Five\n###### Six\n####### Seven";
        $converted = $this->getHelper()->toHtml($markdown);
        $expected = "<h2>One</h2>\n<h3>Two</h3>\n<h4>Three</h4>\n<h5>Four</h5>\n<h6>Five</h6>\n<h6>Six</h6>\n<p>####### Seven</p>\n";
        $this->assertEquals($expected, $converted);
    }

    /**
     * Test replacement of deprecated details tag.
     *
     * @return void
     */
    public function testReplaceDeprecatedDetailsTag()
    {
        $markdown = "<details><summary markdown=\"1\">Summary</summary>Details</details>";
        $converted = $this->getHelper()->replaceDeprecatedTags($markdown);
        $expected = "<finna-panel>\n  <h3 slot=\"heading\">Summary</h3>\n\nDetails\n</finna-panel>\n";
        $this->assertEquals($expected, $converted);
    }

    /**
     * Test replacement of deprecated truncate tag.
     *
     * @return void
     */
    public function testReplaceDeprecatedTruncateTag()
    {
        $markdown = "<truncate><summary>Summary</summary>Truncate</truncate>";
        $converted = $this->getHelper()->replaceDeprecatedTags($markdown);
        $expected = "<finna-truncate>\n  <span slot=\"label\">Summary</span>\nTruncate\n</finna-truncate>\n";
        $this->assertEquals($expected, $converted);
    }

    /**
     * Test Markdown support for the finna-panel custom element.
     *
     * @return void
     */
    public function testFinnaPanel()
    {
        $markdown = <<<EOT
            <finna-panel heading-id="hid" collapse-id="cid">
              <h2 slot="heading">Heading</h2>
              
              **Content**
            </finna-panel>
            EOT;
        $converted = $this->getHelper()->toHtml($markdown);
        $expected = $this->getExpectedFinnaPanel("\n  \n<p><strong>Content</strong></p>\n");
        $this->assertEquals($expected, $converted);
    }

    /**
     * Test Markdown support for nested finna-panel custom elements.
     *
     * @return void
     */
    public function testNestedFinnaPanels()
    {
        $markdown = <<<EOT
            <finna-panel heading-id="hid" collapse-id="cid">
             <h2 slot="heading">Heading</h2>
              
             <finna-panel heading-id="hid" collapse-id="cid">
              <h2 slot="heading">Heading</h2>

              **Content**
             </finna-panel>
            </finna-panel>
            EOT;
        $converted = $this->getHelper()->toHtml($markdown);
        $expected = $this->getExpectedFinnaPanel("\n  \n<p><strong>Content</strong></p>\n");
        $expected = $this->getExpectedFinnaPanel("\n \n$expected");
        $this->assertEquals($expected, $converted);
    }

    /**
     * Test Markdown support for the finna-truncate custom element.
     *
     * @return void
     */
    public function testFinnaTruncate()
    {
        $markdown = <<<EOT
            <finna-truncate>
              <span slot="label">Label</span>
              
              **Content**
            </finna-truncate>
            EOT;
        $converted = $this->getHelper()->toHtml($markdown);
        $expected
            = $this->getHelper()->getView()->render(
                FinnaTruncate::getTemplateName(),
                array_merge(
                    FinnaTruncate::getDefaultVariables(),
                    [
                        'label' => 'Label',
                        'content' => "\n\n<p><strong>Content</strong></p>\n"
                    ]
                )
            ) . "\n";
        $this->assertEquals($expected, $converted);
    }

    /**
     * Test Markdown support for empty single line custom elements.
     *
     * @return void
     */
    public function testSingleLineCustomElement()
    {
        $markdown = "<finna-panel></finna-panel> Extra content";
        $converted = $this->getHelper()->toHtml($markdown);
        $expected = trim($this->getExpectedFinnaPanel(null, null)) . "\n"
            . "<p>Extra content</p>\n";
        $this->assertEquals($expected, $converted);
    }

    protected function getExpectedFinnaPanel(
        ?string $content,
        ?string $heading = 'Heading'
    ): string {
        return $this->getHelper()->getView()->render(
            FinnaPanel::getTemplateName(),
            array_merge(
                FinnaPanel::getDefaultVariables(),
                [
                    'headingId' => 'hid',
                    'collapseId' => 'cid',
                    'heading' => $heading,
                    'content' => $content,
                ]
            )
        ) . "\n";
    }
}
