<?php

/**
 * IIIFManifestGenerator test class.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026.
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
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\IIIF;

use Finna\Record\IIIF\IIIFManifestGenerator;
use Finna\View\Helper\Root\RecordLinker;
use PHPUnit\Framework\TestCase;
use VuFind\Http\RouteHelper;
use VuFind\Http\ServerUrlHelper;
use VuFind\I18n\Locale\LocaleSettings;
use VuFindTest\Feature\ReflectionTrait;

/**
 * IIIFManifestGenerator test class.
 *
 * @category VuFind
 * @package  Tests
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class IIIFManifestGeneratorUnitTest extends TestCase
{
    use ReflectionTrait;

    /**
     * Test that createManifest() called with empty $images returns null
     *
     * @return void
     */
    public function testEmptyImagesReturnsNull(): void
    {
        $generator = new IIIFManifestGenerator(
            $this->createMock(RouteHelper::class),
            $this->createMock(ServerUrlHelper::class),
            $this->createMock(RecordLinker::class),
            $this->createMock(LocaleSettings::class),
        );
        $arguments = [[], '', '', '', ''];
        $manifest = $this->callMethod($generator, 'createManifest', $arguments);
        $this->assertNull($manifest);
    }
}
