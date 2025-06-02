<?php

/**
 * Finna resource list handler entity
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
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\Db\Row;

use DateTime;
use Finna\Db\Entity\FinnaResourceListHandlerEntityInterface;
use VuFind\Db\Row\RowGateway;

/**
 * Finna resource list handler entity
 *
 * @category VuFind
 * @package  Db_Interface
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 *
 * @property int    $id
 * @property string $institution
 * @property string $identifier
 * @property int    $enabled
 * @property string $data
 * @property string $created
 */
class FinnaResourceListHandler extends RowGateway implements
    \VuFind\Db\Service\DbServiceAwareInterface,
    FinnaResourceListHandlerEntityInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait;
    use \VuFind\Db\Service\DbServiceAwareTrait;

    /**
     * Get the ID of the list handler.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get user entity
     *
     * @return string
     */
    public function getInstitution(): string
    {
        return $this->institution;
    }

    /**
     * Get list identifier
     *
     * @return string;
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Is the list enabled
     *
     * @return bool
     */
    public function getEnabled(): bool
    {
        return (bool)$this->enabled;
    }

    /**
     * Get list data as associative array
     *
     * @return array
     */
    public function getData(): array
    {
        return json_decode($this->data, true);
    }

    /**
     * Get time when list has been created
     *
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $this->created);
    }
}
