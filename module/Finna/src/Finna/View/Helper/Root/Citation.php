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
    public function getCitationHarvard()
    {
        $apa = [
            'title' => $this->getAPATitle(),
            'authors' => $this->getAPAAuthors(),
            'edition' => $this->getEdition()
        ];

        $partial = $this->getView()->plugin('partial');
        $apa['publisher'] = $this->getPublisher();
        $apa['year'] = $this->getYear();
        return $partial('Citation/harvard.phtml', $apa);
    }
}?>