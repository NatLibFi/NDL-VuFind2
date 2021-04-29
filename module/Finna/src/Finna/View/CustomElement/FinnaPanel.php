<?php
/**
 * Finna-panel custom element
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
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Finna\View\CustomElement;

/**
 * Finna-panel custom element
 *
 * @category VuFind
 * @package  CustomElements
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class FinnaPanel extends AbstractBase
{
    /**
     * Get the name of the element.
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'finna-panel';
    }

    /**
     * FinnaPanel constructor.
     *
     * @param ?array $options Options
     */
    public function __construct(?array $options = [])
    {
        parent::__construct($options, true);

        $collapsible = $this->attributes['collapsible'] ?? true;

        if ($collapsible) {
            $collapseId = $this->attributes['collapse-id'] ?? uniqid('collapse-');
            $this->viewModel->setVariable('collapseId', $collapseId);
        }

        $heading = $this->dom->find('finna-panel-heading');
        if ($heading = $heading[0] ?? false) {
            $this->viewModel->setVariable(
                'heading', strip_tags($heading->innerHTML())
            );
            $headingAttributes = $this->convertToBool($heading->getAttributes());
            if ($collapsible) {
                $headingId = $headingAttributes['id'] ?? uniqid('heading-');
                $this->viewModel->setVariable('headingId', $headingId);
            }
            $headingLevel = $headingAttributes['level'] ?? 2;
            $this->viewModel->setVariable('headingLevel', $headingLevel);
            $headingTag = $headingAttributes['htag'] ?? true;
            $this->viewModel->setVariable('headingTag', $headingTag);
            $heading->getParent()->removeChild($heading->id());
        }

        $this->viewModel->setVariable(
            'content', $this->dom->firstChild()->innerHTML()
        );

        $this->viewModel->setTemplate(
            'components/molecules/containers/finna-panel/finna-panel'
        );
    }

    /**
     * Get default values for view model variables.
     *
     * @return array
     */
    protected function getDefaultVariableValues(): array
    {
        return ['attributes' => ['class' => 'finna-panel-default']];
    }

    /**
     * Get names of attributes to set as view model variables.
     *
     * @return array Keyed array with attribute names as keys and variable names as
     *               values
     */
    protected function getVariableAttributes(): array
    {
        return ['collapsed' => 'collapsed'];
    }
}
