<?php

/**
 * Finna specific helper trait for functions
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
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace FinnaTest\Traits;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * Finna specific helper trait for functions
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
trait FinnaHelperTrait
{
    /**
     * Get a mock object assuming the methods are all callbacks and expects($this->any())
     *
     * @param string $classToMock         Class to mock
     * @param array  $methodsAndCallbacks Methods to mock, key is the method and value is a callback
     *
     * @return MockObject
     */
    public function getMockedObject(string $classToMock, array $methodsAndCallbacks = []): MockObject
    {
        $methodKeys = array_keys($methodsAndCallbacks);
        $loader = $this->container->createMock($classToMock, $methodKeys);
        foreach ($methodsAndCallbacks as $method => $callback) {
            $loader->expects($this->any())->method($method)->willReturnCallback($callback);
        }
        return $loader;
    }
}
