<?php

/**
 * Resolve path to theme resource.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015.
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
 * @package  View_Helpers
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * Resolve path to theme resource.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class ThemeSrc extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Theme information service
     *
     * @var \VufindTheme\ThemeInfo
     */
    protected $themeInfo;

    /**
     * Constructor
     *
     * @param \VuFindTheme\ThemeInfo $themeInfo Theme information service
     */
    public function __construct(\VuFindTheme\ThemeInfo $themeInfo)
    {
        $this->themeInfo = $themeInfo;
    }

    /**
     * Returns filepath from current theme if found.
     *
     * @param string $relPath           File relative path
     * @param bool   $returnAbsolute    Whether to return absolute file system path
     * @param bool   $allowParentThemes If file can be searched from parent themes
     *
     * @return mixed
     */
    protected function fileFromCurrentTheme(
        $relPath, $returnAbsolute = false, $allowParentThemes = false
    ) {
        $currentTheme = $this->themeInfo->getTheme();
        $basePath = $this->themeInfo->getBaseDir();

        if (!$allowParentThemes) {
            $file = $basePath . '/' . $currentTheme . '/' . $relPath;
            if (file_exists($file)) {
                if ($returnAbsolute) {
                    return $file;
                }
                $urlHelper = $this->getView()->plugin('url');
                return $urlHelper('home') . 'themes/' .
                    $currentTheme . '/' . $relPath;
            }
        } else {
            $results = $this->themeInfo->findInThemes($relPath);
            foreach ($results as $result) {
                if (!empty($result)) {
                    if ($returnAbsolute) {
                        return $result['file'];
                    }
                    $urlHelper = $this->getView()->plugin('url');
                    return $urlHelper('home') . 'themes/' .
                        $result['theme'] . '/' . $result['relativeFile'];
                }
            }
        }
        return null;
    }
}
