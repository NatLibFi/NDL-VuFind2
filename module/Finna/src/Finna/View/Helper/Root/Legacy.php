<?php

/**
 * Backwards compatibility helper
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\View\Helper\Root;

use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;
use VuFind\View\Helper\Root\ClassBasedTemplateRendererTrait;
use VuFindTheme\ThemeInfo;

/**
 * Backwards compatibility helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Legacy extends AbstractHelper
{
  use ClassBasedTemplateRendererTrait;

  /**
   * Constructor
   *
   * @param Config    $legacyConfig      Legacy configuration 
   * @param ThemeInfo $themeInfo         Theme info helper
   * @param array     $markedTemplates   Templates which has been marked as legacy. If the Site has a custom template
   *                                     present, this will load template with .legacy addition to load data which prevents
   *                                     breaking styles.
   * @return void
   */
  public function __construct(
      protected Config $legacyConfig,
      protected ThemeInfo $themeInfo,
      protected array $markedTemplates = []
  ) {
  }

  /**
   * Check if the template is marked as legacy. This will cause additional files to be loaded if the site
   * has that template overwritten in custom folder.
   *
   * @param string $name Name of the template
   * @param string $name Class name of the driver
   *
   * @return string
   */
  public function __invoke(string $name, string $className): string
  {
    if (!$this->markedTemplates) {
      return $name;
    }
    if ($section = $this->markedTemplates[$name] ?? null && $this->hasCustomTemplate($name)) {
      // If the template is found with single name without resolving, then try to find the file
      $this->appendLegacyDependencies($section);
      return $name;
    }

    $template = 'RecordDriver/%s/' . $name;
    $resolved = $this->resolveClassTemplate(
        $template,
        $className,
        $this->getView()->resolver()
    );
    if ($section = $this->markedTemplates[$resolved] ?? null && $this->hasCustomTemplate($name)) {
      // If the template is found with single name without resolving, then try to find the file
      $this->appendLegacyDependencies($section);
    }
    return $name;
  }

  /**
   * Add legacy dependencies required for styles and scripts to work properly.
   *
   * @param string $sectionName Section name which contains js and/or css paths.
   *
   * @return void
   */
  protected function appendLegacyDependencies(string $sectionName): void
  {
    $sectionConfig = $this->legacyConfig->$sectionName ?? [];
    if (!$sectionConfig) {
      return;
    }
    $headScript = $this->getView()->plugin('headScript');
    foreach ($sectionConfig->scripts ?? [] as $script) {
      $headScript->appendFile($script);
    }
    $headLink = $this->getView()->plugin('headLink');
    foreach ($sectionConfig->styles ?? [] as $style) {
      $headLink->appendStylesheet($style);
    }
  }

  /**
   * Check if the current site has template overwritten in custom theme.
   *
   * @param string $templatePath Template path to check
   *
   * @return bool
   */
  protected function hasCustomTemplate(string $templatePath): bool
  {
    if ($result = $this->themeInfo->findInThemes('templates/' . $templatePath)) {
      $theme = array_shift($result);
      return $theme['theme'] === 'custom';
    }
    return false;
  }
}
