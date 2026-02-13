<?php

/**
 * BiblioworksChatbot view helper test class.
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
 * @package  Tests
 * @author   BiblioWorks <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */

namespace FinnaTest\View\Helper\Root;

use Finna\View\Helper\Root\BiblioworksChatbot;

/**
 * BiblioworksChatbot view helper test class.
 *
 * @category VuFind
 * @package  Tests
 * @author   BiblioWorks <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */
class BiblioworksChatbotTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that the helper returns empty string when chatbot is disabled.
     *
     * @return void
     */
    public function testReturnsEmptyStringWhenDisabled(): void
    {
        $config = [
            'Chatbot' => [
                'chatbot_enabled' => false,
            ],
        ];
        $helper = new BiblioworksChatbot($config);
        $this->assertSame('', $helper());
    }
}
