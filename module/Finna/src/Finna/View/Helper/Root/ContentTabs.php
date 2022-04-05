<?php
/**
 * View helper for content tabs.
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
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\View\Helper\Root;

/**
 * View helper for content tabs.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ContentTabs extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Returns HTML for the widget.
     *
     * @param array $data Data for the content tabs.
     *
     * @return string
     */
    public function __invoke(array $data)
    {
        $title = $data['title'] ?? '';
        $contents = $data['contents'];
        return $this->getView()->render(
            'Helpers/contenttabs.phtml',
            [
                'title' => $title,
                'id' => md5(json_encode($contents)),
                'contents' => $contents,
                'active' => array_shift($contents)
            ]
        );
    }
}
