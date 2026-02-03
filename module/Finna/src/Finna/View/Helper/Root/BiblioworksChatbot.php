<?php

/**
 * BiblioWorks Chatbot View Helper
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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
 * @author   BiblioWorks <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */

namespace Finna\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering BiblioWorks Helpdesk chatbot integration
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   BiblioWorks <andrea@biblioworks.ai>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/NDL-VuFind2
 */
class BiblioworksChatbot extends AbstractHelper
{
    /**
     * BiblioWorks configuration
     *
     * @var array
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array $config BiblioWorks configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Render the chatbot integration scripts if enabled
     *
     * @return string HTML output for chatbot integration
     */
    public function __invoke()
    {
        $chatbotConfig = $this->config['Chatbot'] ?? [];

        $chatbotEnabled = $chatbotConfig['chatbot_enabled'] ?? false;

        if (!$chatbotEnabled) {
            return '';
        }

        $token = $chatbotConfig['chatbot_token'] ?? '';
        $baseUrl = $chatbotConfig['chatbot_base_url'] ?? '';

        if (empty($token) || empty($baseUrl)) {
            return '';
        }

        // Escape values for safe output
        $escapeAttr = $this->getView()->plugin('escapeHtmlAttr');

        $tokenJson = json_encode($token);
        $baseUrlJson = json_encode($baseUrl);
        $baseUrlAttr = $escapeAttr($baseUrl);

        // Get CSP nonce for inline scripts
        $cspNonce = $this->getView()->plugin('csp')->getNonce();

        return <<<HTML
<script nonce="{$cspNonce}">
  window.helpdeskChatbotConfig = {
    token: {$tokenJson},
    baseUrl: {$baseUrlJson},
    dynamicScript: true,
  };
</script>
<script src="{$baseUrlAttr}/helpdesk-chatbot.min.js" nonce="{$cspNonce}"></script>
HTML;
    }
}
