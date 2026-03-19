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
use Finna\RecordDriver\SolrLido;
use Finna\View\Helper\Root\RecordLinker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use VuFind\Http\RouteHelper;
use VuFind\Http\ServerUrlHelper;
use VuFind\I18n\Locale\LocaleSettings;
use VuFindTest\Feature\FixtureTrait;
use VuFindTest\Feature\ReflectionTrait;
use VuFindTest\Feature\TranslatorTrait;

/**
 * IIIFManifestGenerator test class.
 *
 * @category VuFind
 * @package  Tests
 * @author   Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class IIIFManifestGeneratorIntegrationTest extends TestCase
{
    use FixtureTrait;
    use ReflectionTrait;
    use TranslatorTrait;

    /**
     * Initialize mock IIIFManifestGenerator, get a fake SolrLido record,
     * generate a manifest, then validate the record against the JSON schema.
     *
     * @param string $xmlFixture      Path to LIDO fixture file
     * @param bool   $expectedSuccess generate() expected to return object
     *
     * @return void
     */
    #[DataProvider('getLidoFixtures')]
    public function testGeneratedManifest(
        string $xmlFixture,
        bool $expectedSuccess
    ): void {
        // init mock record linker
        $recordLinker = $this->createMock(RecordLinker::class);
        $recordLinker
            ->method('getGeneratedIiifManifestUrl')
            ->willReturn('http://example.com/Record/test_record/IIIFManifest');
        // init mock manifest generator
        $generator = $this->getMockBuilder(IIIFManifestGenerator::class)
            ->setConstructorArgs([
                $this->createMock(RouteHelper::class),
                $this->createMock(ServerUrlHelper::class),
                $recordLinker,
                $this->createMock(LocaleSettings::class),
            ])
            ->onlyMethods(['createBodyId', 'getTranslations'])
            ->getMock();
        $generator->method('createBodyId')
            ->willReturnCallback(
                fn (
                    string $recordId,
                    int $index,
                    string $size,
                    string $source
                ): string
                => 'http://example.com/Cover/Show/'
                   . "$recordId?index=$index&size=$size&source="
                   . strtolower($source)
            );
        $generator->method('getTranslations')
            ->willReturnCallback(fn (string $message): object => (object)['en' => [$message]]);

        // init mock record driver

        $driver = $this->getFakeSolrLido($xmlFixture);

        $manifest = $generator->generate($driver);
        if ($expectedSuccess) {
            $this->assertIsObject($manifest);
        } else {
            $this->assertNull($manifest);
        }

        if (null === $manifest) {
            return;
        }

        $this->patchCanvasDimensions($manifest);

        // run schema validator

        try {
            $this->validateManifest($manifest);
        } catch (\Swaggest\JsonSchema\Exception $e) {
            $this->fail(
                'IIIF manifest generator validation failed for: ' . var_export($manifest, true) . PHP_EOL .
                $e->getMessage()
            );
        }
    }

    /**
     * Get LIDO fixture files and expected return types
     *
     * @return \Generator<array<string>>
     */
    public static function getLidoFixtures(): \Generator
    {
        yield 'LIDO record with 1 image' =>
            ['iiif/lido_one_large.xml', true];
        yield 'LIDO record with 3 images' =>
            ['iiif/lido_three.xml', true];
        yield 'LIDO record with 0 images' =>
            ['iiif/lido_none.xml', false];
    }

    /**
     * Coherence-check the validator with guaranteed-invalid JSON
     *
     * @param string $input Input JSON
     *
     * @return void
     */
    #[DataProvider('getInvalidInputs')]
    public function testSchemaValidatorInvalidInput(string $input): void
    {
        $this->expectException(\TypeError::class);
        $this->validateManifest(json_decode($input));
    }

    /**
     * Get invalid inputs for the schema validator
     *
     * @return \Generator<array<string>>
     */
    public static function getInvalidInputs(): \Generator
    {
        yield [''];
        yield ['[]'];
        yield ["{'status': 500}"];
    }

    /**
     * Create a mock SolrLido record
     *
     * @param string $recordXml    Path to LIDO fixture file
     * @param array  $searchConfig searchSettings for SolrLido
     * @param array  $rawData      Raw data to set in record
     * @param string $language     Language for Laminas\i18n\Translator
     *
     * @return SolrLido
     */
    protected function getFakeSolrLido(
        string $recordXml,
        $searchConfig = [],
        $rawData = [],
        $language = 'en',
    ): SolrLido {
        $fixture = $this->getFixture($recordXml, 'Finna');
        $config = [
            'Record' => [
                'allowed_external_hosts_mode' => 'disable',
            ],
            'FileDownload' => [
                'excludeRights' => [
                    'INC',
                ],
            ],
            'Models' => [
                'previewImages' => [
                    'test' => true,
                ],
            ],
            'Site' => [
                'url' => 'http://example.com',
            ],
        ];
        $config = new \VuFind\Config\Config($config);
        $record = new SolrLido(
            $config,
            $config,
            new \VuFind\Config\Config($searchConfig),
        );
        $defaultData = [
            'id' => 'knp-247394',
            'source_str_mv' => [
                'test',
            ],
            'fullrecord' => $fixture,
            'usage_rights_str_mv' => [
                'usage_A',
            ],
        ];
        $record->setRawData(
            array_merge($defaultData, $rawData)
        );

        $translator = $this
            ->getMockTranslator([], 'en');
        $record->setTranslator($translator);

        $dateConverter = new \VuFind\Date\Converter([
            'displayDateFormat' => 'd-m-Y',
        ]);
        $record->attachDateConverter($dateConverter);
        return $record;
    }

    /**
     * Force the width and height fields on each member of $manifest['items'].
     *
     * This avoids a JSON schema validation error, because these values are
     * mandatory, even if our TIFY viewer gets by without them. The actual
     * manifest generator omits these.
     *
     * @param array $manifest Generated manifest
     *
     * @return void
     */
    protected function patchCanvasDimensions(object &$manifest): void
    {
        foreach ($manifest->items as &$canvas) {
            $canvas->width = 1000;
            $canvas->height = 1000;
        }
        unset($canvas);
    }

    /**
     * Use swaggest/json-schema to validate Presentation API manifest
     *
     * The JSON schema fixture file is from:
     * https://github.com/IIIF/presentation-validator/blob/6fe43b8d6e27f12f83bd99b31125e3821e60ba7b/schema/iiif_3_0.json
     *
     * @param object $manifest Manifest
     *
     * @return void
     *
     * @throws \Swaggest\JsonSchema\Exception
     */
    protected function validateManifest(object $manifest): void
    {
        $schema = Schema::import(
            'file://' . $this->getFixturePath(
                'iiif/schema/iiif_3_0.json',
                'Finna'
            )
        );

        $options = new Context();
        $options->validateOnly = true;
        $schema->in((object)$manifest);
    }
}
