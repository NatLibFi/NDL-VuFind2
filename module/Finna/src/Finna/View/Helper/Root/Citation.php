<?php
/**
 * Citation view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2017.
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
 * @author   Anna Niku <anna.niku@gofore.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\View\Helper\Root;

/**
 * Citation view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Anna Niku <anna.niku@gofore.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Citation extends \VuFind\View\Helper\Root\Citation
{
    /**
     * Get Harvard citation.
     *
     * This function assigns all the necessary variables using APA's functions
     * and then returns an Harvard citation.
     *
     * @return string
     */
    public function getCitationHarvard()
    {
        $harvard = [
            'title' => $this->getAPATitle(),
            'authors' => $this->getAPAAuthors()
        ];

        $harvard['periodAfterTitle']
            = (!$this->isPunctuated($harvard['title']) && empty($harvard['edition']));

        $partial = $this->getView()->plugin('partial');
        if (empty($this->details['journal'])) {
            $harvard['edition'] = $this->getEdition();
            $harvard['publisher'] = $this->getPublisher();
            $harvard['year'] = $this->getYear();
            return $partial('Citation/harvard.phtml', $harvard);
        } else {
            list($harvard['volume'], $harvard['issue'], $harvard['date'])
                = $this->getAPANumbersAndDate();
            $harvard['journal'] = $this->details['journal'];
            $harvard['pageRange'] = $this->getPageRange();
            if ($doi = $this->driver->tryMethod('getCleanDOI')) {
                $harvard['doi'] = $doi;
            }
            return $partial('Citation/harvard-article.phtml', $harvard);
        }
    }
}?>