<?php
/**
 * Feed service
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2016.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Content
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\Feed;
use Zend\Config\Config,
    Zend\Feed\Reader\Reader,
    Zend\Http\Request as HttpRequest;

/**
 * Feed service
 *
 * @category VuFind
 * @package  Content
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Feed
{
    /**
     * Main configuration.
     *
     * @var Zend\Config\Config
     */
    protected $mainConfig;

    /**
     * Feed configuration.
     *
     * @var Zend\Config\Config
     */
    protected $feedConfig;

    /**
     * Http service
     *
     * @var VuFind\Http
     */
    protected $http;

    /**
     * Translator
     *
     * @var VuFind\Translator
     */
    protected $translator;

    /**
     * Cache manager
     *
     * @var VuFind\Translator
     */
    protected $cacheManager;

    /**
     * Constructor.
     *
     * @param VuFind\Config       $config       Main configuration
     * @param VuFind\Config       $feedConfig   Feed configuration
     * @param VuFind\Http         $http         Http service
     * @param VuFind\Translator   $translator   Translator
     * @param VuFind\CacheManager $cacheManager Cache manager
     */
    public function __construct(
        $config, $feedConfig, $http, $translator, $cacheManager
    ) {
        $this->mainConfig = $config;
        $this->feedConfig = $feedConfig;
        $this->http = $http;
        $this->translator = $translator;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get feed configuration.
     *
     * Returns an array containing:
     *   - VuFind\Config Feed configuration
     *   - string        Feed URL
     *
     * @param string $id Feed id
     *
     * @return array
     * @throws Exception
     */
    protected function getFeedConfig($id)
    {
        if (!isset($this->feedConfig[$id])) {
            throw new \Exception('Missing feed configuration');
        }

        $result = $this->feedConfig[$id];
        if (!$result->active) {
            throw new \Exception('Feed inactive');
        }

        if (empty($result->url)) {
            throw new \Exception('Missing feed URL');
        }

        $language   = $this->translator->getLocale();

        $url = $result->url;
        if (isset($url[$language])) {
            $url = trim($url[$language]);
        } else if (isset($url['*'])) {
            $url = trim($url['*']);
        } else {
            throw new \Exception('Missing feed URL');
        }

        return [$result, $url];
    }

    /**
     * Utility function for extracting an image URL from a HTML snippet.
     *
     * @param string $html HTML snippet.
     *
     * @return mixed null|string
     */
    protected function extractImage($html)
    {
        if (empty($html)) {
            return null;
        }
        $doc = new \DOMDocument();
        // Silence errors caused by invalid HTML
        libxml_use_internal_errors(true);
        if (!$doc->loadHTML($html)) {
            return null;
        }
        libxml_clear_errors();

        $img = null;
        $imgs = iterator_to_array($doc->getElementsByTagName('img'));
        if (!empty($imgs)) {
            $img = $imgs[0];
        }

        return $img ? $img->getAttribute('src') : null;
    }

    /**
     * Return feed content and settings in an array containing:
     *   - Zend\Feed\Reader\Feed\Rss Feed
     *   - array                     Feed item data
     *   - VuFind\Config             Feed configuration
     *   - boolean                   Display feed content in a modal
     *
     * @param string                         $id        Feed id
     * @param Zend\Mvc\Controller\Plugin\Url $urlHelper Url helper
     * @param string                         $viewUrl   View URL
     *
     * @return mixed null|array
     */
    public function readFeed($id, $urlHelper, $viewUrl)
    {
        list($config, $url, $language) = $this->getFeedConfig($id);
        $type = $config->type;

        $cacheKey = $config->toArray();
        $cacheKey['language'] = $this->translator->getLocale();

        $modal = false;
        $showFullContentOnSite = isset($config->linkTo)
            && in_array($config->linkTo, ['modal', 'content-page']);

        $modal = $config->linkTo == 'modal';
        $dateFormat = isset($config->dateFormat) ? $config->dateFormat : 'j.n.';
        $itemsCnt = isset($config->items) ? $config->items : null;
        $elements = isset($config->content) ? $config->content : [];

        $channel = null;

        // Check for cached version
        $readFromCache = true;
        $cacheDir
            = $this->cacheManager->getCache('feed')->getOptions()->getCacheDir();

        $localFile = "$cacheDir/" . md5(var_export($cacheKey, true)) . '.xml';
        $maxAge = isset($this->mainConfig->Content->feedcachetime)
            ? $this->mainConfig->Content->feedcachetime : 10;

        Reader::setHttpClient($this->http->createClient());

        if ($maxAge) {
            if (is_readable($localFile)
                && time() - filemtime($localFile) < $maxAge * 60
            ) {
                $channel = Reader::importFile($localFile);
            }
        }

        if (!$channel) {
            $readFromCache = false;

            // No cache available, read from source.
            if (preg_match('/^http(s)?:\/\//', $url)) {
                // Absolute URL
                $channel = Reader::import($url);
            } else if (substr($url, 0, 1) === '/') {
                // Relative URL
                $url = substr($viewUrl, 0, -1) . $url;
                $channel = Reader::import($url);
            } else {
                // Local file
                if (!is_file($url)) {
                    throw new \Exception("File $url could not be found");
                }
                $channel = Reader::importFile($url);
            }
        }

        if (!$channel) {
            return false;
        }

        if (!$readFromCache) {
            file_put_contents($localFile, $channel->saveXml());
        }

        $content = [
            'title' => 'getTitle',
            'text' => 'getContent',
            'image' => 'getEnclosure',
            'link' => 'getLink',
            'date' => 'getDateCreated',
        ];

        $xpathContent = [
            'html' => '//item/content:encoded'
        ];

        $items = [];
        $cnt = 0;
        $xpath = null;

        $cnt = 0;
        foreach ($channel as $item) {
            if (!$xpath) {
                $xpath = $item->getXpath();
            }
            $data = [];
            $data['modal'] = $modal;
            foreach ($content as $setting => $method) {
                if (!isset($elements[$setting])
                    || $elements[$setting] != 0
                ) {
                    $tmp = $item->{$method}();
                    if (is_object($tmp)) {
                        $tmp = get_object_vars($tmp);
                    }

                    if ($setting == 'image') {
                        if (!$tmp
                            || stripos($tmp['type'], 'image') === false
                        ) {
                            // Attempt to parse image URL from content
                            if ($tmp = $this->extractImage($item->getContent())) {
                                $tmp = ['url' => $tmp];
                            }
                        }
                    } else if ($setting == 'date') {
                        if (isset($tmp['date'])) {
                            $tmp = new \DateTime(($tmp['date']));
                            if ($dateFormat) {
                                $tmp = $tmp->format($dateFormat);
                            }
                        }
                    } else if ($setting == 'link' && $showFullContentOnSite) {
                        $link = $urlHelper->fromRoute(
                            'feed-content-page',
                            ['page' => $id, 'element' => $cnt]
                        );
                        $tmp = $link;
                    } else {
                        if (is_string($tmp)) {
                            $tmp = strip_tags($tmp);
                        }
                    }
                    if ($tmp) {
                        $data[$setting] = $tmp;
                    }
                }
            }

            // Make sure that we have something to display
            $accept = $data['title'] && trim($data['title']) != ''
                || $data['text'] && trim($data['text']) != ''
                || $data['image']
            ;
            if (!$accept) {
                continue;
            }

            $items[] = $data;
            if ($itemsCnt !== null) {
                if (--$itemsCnt === 0) {
                    break;
                }
            }
            $cnt++;
        }

        if (!empty($xpathContent)) {
            if ($xpathItem = $xpath->query('//item/content:encoded')->item(0)) {
                $contentSearch = isset($config->htmlContentSearch)
                    ? $config->htmlContentSearch->toArray() : [];

                $contentReplace = isset($config->htmlContentReplace)
                    ? $config->htmlContentReplace->toArray() : [];

                $searchReplace = array_combine($contentSearch, $contentReplace);

                $cnt = 0;
                foreach ($items as &$item) {
                    foreach ($xpathContent as $setting => $xpathElement) {
                        $content = $xpath->query($xpathElement, $xpathItem)
                            ->item($cnt++)->nodeValue;

                        // Remove width & height declarations from style
                        // attributes in div & p elements
                        $dom = new \DOMDocument();
                        libxml_use_internal_errors(false);
                        $dom->loadHTML(
                            mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8')
                        );
                        $domx = new \DOMXPath($dom);
                        $elements = $domx->query('//div[@style]|//p[@style]');

                        foreach ($elements as $el) {
                            $styleProperties = [];
                            $styleAttr = $el->getAttribute('style');
                            $properties = explode(';', $styleAttr);
                            foreach ($properties as $prop) {
                                list($field, $val) = explode(':', $prop);
                                if (stristr($field, 'width') === false
                                    && stristr($field, 'height') === false
                                ) {
                                    $styleProperties[] = $prop;
                                }
                            }
                            $el->removeAttribute("style");
                            $el->setAttribute(
                                'style', implode(';', $styleProperties)
                            );
                        }
                        $content = $dom->saveHTML();

                        // Process feed specific search-replace regexes
                        foreach ($searchReplace as $search => $replace) {
                            $pattern = "/$search/";
                            $replaced = preg_replace($pattern, $replace, $content);
                            if ($replaced) {
                                $content = $replaced;
                            }
                        }
                        $item[$setting] = $content;
                    }
                }
            }
        }
        return [$channel, $items, $config, $modal];
    }
}
