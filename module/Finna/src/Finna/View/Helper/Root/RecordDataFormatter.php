<?php

/**
 * Record driver data formatting view helper
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2016.
 * Copyright (C) The National Library of Finland 2017-2023.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma  <juha.luoma@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */

namespace Finna\View\Helper\Root;

use Finna\View\Helper\Root\RecordDataFormatter\FieldGroupBuilder;
use VuFind\RecordDriver\AbstractBase as RecordDriver;

use function call_user_func;
use function is_array;
use function is_callable;

/**
 * Record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma  <juha.luoma@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class RecordDataFormatter extends \VuFind\View\Helper\Root\RecordDataFormatter
{
    /**
     * Helper method for getting a spec of field groups from FieldGroupBuilder.
     *
     * @param array  $groups        Array specifying the groups. See
     *                              FieldGroupBuilder::addGroup() for details.
     * @param array  $lines         All lines used in the groups. If this contains
     *                              lines not specified in $groups, all unused lines
     *                              will be appended as their own group.
     * @param string $template      Default group template to use if not specified
     *                              for a group (optional, set to null to use the
     *                              default value).
     * @param array  $options       Additional options to be merged with group
     *                              specific additional options (optional, set to
     *                              null to use the default value). See
     *                              FieldGroupBuilder::addGroup() for details.
     * @param array  $unusedOptions Additional options for the unused lines group
     *                              (optional, set to null to use the default value).
     *                              See FieldGroupBuilder::addGroup()
     *                              for details.
     *
     * @return array
     */
    public function getGroupedFields(
        $groups,
        $lines,
        $template = null,
        $options = null,
        $unusedOptions = null
    ) {
        $template ??= 'core-field-group-fields.phtml';
        $options ??= [];
        $unusedOptions ??= $options;

        $fieldGroups = new FieldGroupBuilder();
        $fieldGroups->setGroups(
            $groups,
            $lines,
            $template,
            $options,
            $unusedOptions
        );
        return $fieldGroups->getArray();
    }

    /**
     * Create formatted key/value data based on a record driver and grouped
     * field spec.
     *
     * @param RecordDriver $driver Record driver object.
     * @param array        $groups Grouped formatting specification.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupedData(RecordDriver $driver, array $groups)
    {
        // Apply the group spec.
        $result = [];
        foreach ($groups as $group) {
            if (!empty($group['skipGroup'])) {
                continue;
            }
            $lines = $group['lines'];
            $data = $this->getData($driver, $lines);
            if (empty($data)) {
                continue;
            }
            // Render the fields in the group as the value for the group.
            $value = $this->renderRecordDriverTemplate(
                $data,
                ['template' => $group['template']]
            );
            $result[] = [
                'label' => $group['label'],
                'value' => $value,
                'context' => $group['options']['context'] ?? [],
            ];
        }
        return $result;
    }

    /**
     * Get default configuration.
     *
     * @param string $key Key for configuration to look up.
     *
     * @return array
     */
    public function getDefaults($key = 'core'): array
    {
        $specs = $this->getSpecPluginForDriver();
        if ($specs === null) {
            throw new \Exception('Using the RecordDataFormatter view helper with a driver that is not supported.');
        }
        $specs->setDatasource($this->driver->getDatasource());
        return $specs->getDefaults($key);
    }

    /**
     * Return rendered text (or null if nothing to render).
     *
     * @param string $field   Field being rendered (i.e. default label)
     * @param mixed  $data    Data to render
     * @param array  $options Rendering options
     *
     * @return ?array
     */
    protected function render(string $field, mixed $data, array $options): ?array
    {
        // Allow dynamic label override:
        $label = is_callable($options['labelFunction'] ?? null)
            ? call_user_func($options['labelFunction'], $data, $this->driver)
            : $field;

        // Support searching for label from array.
        if (is_array($label)) {
            $translationEmpty = $this->getView()->plugin('translationEmpty');
            $foundLabel = '';
            foreach ($label as $key) {
                if (!($translationEmpty)($key)) {
                    $foundLabel = $key;
                    break;
                }
            }
            // Unset current label function as running it second time is unnecessary
            unset($options['labelFunction']);
            $label = $foundLabel;
        }
        return parent::render($label, $data, $options);
    }
}
