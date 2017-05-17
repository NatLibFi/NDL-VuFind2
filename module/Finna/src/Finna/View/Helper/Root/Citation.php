<?php

namespace Finna\View\Helper\Root;
use VuFind\Exception\Date as DateException;


/**
 * Holdings callnumber view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Anna Niku <anna.niku@gofore.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Citation extends \VuFind\View\Helper\Root\Citation
{
    /**
     * Get Harvard citation.
     *
     * This function assigns all the necessary variables and then returns an Harvard
     * citation.
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