<?php

/**
 * Trait for organization product code mappings.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\OnlinePayment\Handler\Feature;

/**
 * Trait for organization product code mappings.
 *
 * @category VuFind
 * @package  OnlinePayment
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
trait OrganizationProductCodeMappingsTrait
{
    /**
     * Organization-specific product code mappings.
     *
     * @var array
     */
    protected $organizationProductCodeMappings = [];

    /**
     * Initialize the handler.
     *
     * @param array $paymentConfig Online payment configuration
     *
     * @return void
     */
    public function init(array $paymentConfig): void
    {
        parent::init($paymentConfig);
        $this->organizationProductCodeMappings
            = $this->parseMappings($this->paymentConfig['organizationProductCodeMappings'] ?? '');
    }

    /**
     * Get a product code for a fine.
     *
     * @param array $fine Fine
     *
     * @return ?string
     */
    protected function getFineProductCode(array $fine): ?string
    {
        $fineOrg = $fine['organization'] ?? '';
        if (null !== ($orgProductCode = $this->organizationProductCodeMappings[$fineOrg] ?? null)) {
            return $orgProductCode;
        }

        return parent::getFineProductCode($fine);
    }
}
