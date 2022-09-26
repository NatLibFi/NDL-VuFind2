<?php
/**
 * Iframe helper
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
 * @package  Content
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;

/**
 * Iframe helper
 *
 * @category VuFind
 * @package  Content
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Iframe extends \Laminas\View\Helper\AbstractHelper
    implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * Cookie consent configuration
     *
     * @var array
     */
    protected $consentConfig;

    /**
     * Constructor
     *
     * @param array $consentConfig Cookie consent configuration
     */
    public function __construct(array $consentConfig)
    {
        $this->consentConfig = $consentConfig;
    }

    /**
     * Render a generic iframe or link box depending on cookie consent
     *
     * @param string $style             Element style attribute used for both iframe
     * and possible placeholder div if required consent categories are not accepted
     * @param string $title             Iframe title
     * @param string $src               Iframe src attribute
     * @param array  $attributes        Other iframe attributes (if this contains
     * style, it overrides the style from the $style parameter for the iframe)
     * @param string $serviceUrl        URL to the service's own interface
     * @param array  $consentCategories Required cookie consent categories
     *
     * @return string
     */
    public function render(
        string $style,
        string $title,
        string $src,
        array $attributes,
        string $serviceUrl,
        array $consentCategories
    ): string {
        if ($urlParts = parse_url($serviceUrl)) {
            $serviceBaseUrl = '';
            if ($scheme = $urlParts['scheme'] ?? '') {
                $serviceBaseUrl = "$scheme://";
            }
            $serviceBaseUrl .= $urlParts['host'];
            if ($port = $urlParts['port'] ?? '') {
                $serviceBaseUrl = ":$port";
            }
        } else {
            $serviceBaseUrl = $serviceUrl;
        }
        $consentCategoriesTranslated = [];
        foreach ($consentCategories as $category) {
            $consentCategoriesTranslated[]
                = $this->translate(
                    $this->consentConfig['Categories'][$category]['Title']
                    ?? 'Unknown'
                );
        }

        return $this->getView()->render(
            'Helpers/iframe.phtml',
            compact(
                'style',
                'title',
                'src',
                'attributes',
                'serviceUrl',
                'consentCategories',
                'consentCategoriesTranslated',
                'serviceBaseUrl'
            )
        );
    }

    /**
     * Render a YouTube iframe or link box depending on cookie consent
     *
     * @param string $videoId           Video ID
     * @param array  $consentCategories Required cookie consent categories
     * @param string $width             Element width (e.g. 512px)
     * @param string $height            Element height (e.g. 384px)
     * @param array  $attributes        Other iframe attributes (if this contains
     * style, it overrides the style from the $style parameter for the iframe)
     *
     * @return string
     */
    public function youtube(
        string $videoId,
        array $consentCategories,
        string $width,
        string $height,
        array $attributes = []
    ): string {
        if (!isset($attributes['allow'])) {
            $attributes['allow'] = 'accelerometer; autoplay; clipboard-write;'
                . ' encrypted-media; gyroscope; picture-in-picture';
        }
        return $this->render(
            "width: $width; height: $height;",
            'YouTube video player',
            'https://www.youtube.com/embed/' . urlencode($videoId),
            $attributes,
            'https://www.youtube.com/watch?v=' . urlencode($videoId),
            $consentCategories
        );
    }
}
