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
use FinnaTest\Traits\LogTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use VuFind\Http\RouteHelper;
use VuFind\Http\ServerUrlHelper;
use VuFind\I18n\Locale\LocaleSettings;
use VuFindTest\Feature\FixtureTrait;
use VuFindTest\Feature\ReflectionTrait;

use function gettype;
use function is_resource;
use function json_encode;

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
    use LogTrait;
    use FixtureTrait;
    use ReflectionTrait;

    /**
     * Utility method for running an external IIIF manifest validator
     *
     * @param string|array $command Validator command
     * @param string       $json    Manifest JSON
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function runExternalValidator(
        string|array $command,
        string $json
    ): ExternalValidatorOutput {
        $spec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $spec, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to run external IIIF manifest validator');
        }

        fwrite($pipes[0], $json);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitStatus = proc_close($process);

        return new ExternalValidatorOutput($exitStatus, $stdout, $stderr);
    }

    #[DataProvider('getTestManifestGeneratorImageData')]
    public function testGeneratedManifest(
        array $arguments,
        string $expectedReturnType
    ): void {
        $generator = $this->getMockBuilder(IIIFManifestGenerator::class)
            ->setConstructorArgs([
                $this->createMock(RouteHelper::class),
                $this->createMock(ServerUrlHelper::class),
                $this->createMock(RecordLinker::class),
                $this->createMock(LocaleSettings::class),
            ])
            ->onlyMethods(['createBodyId', 'getTranslations'])
            ->getMock();
        $generator->method('createBodyId')
            ->willReturnCallback(
                fn ($recordId, $index, $size, $source)
                => "http://example.com/Cover/Show/$recordId?index=$index&size=$size&source=$source"
            );
        $generator->method('getTranslations')
            ->willReturnCallback(fn ($message) => ['en' => $message]);

        $manifest = $this->callMethod($generator, 'createManifest', $arguments);
        $this->assertEqualsIgnoringCase($expectedReturnType, gettype($manifest));

        $manifestJson = json_encode($manifest);
        $validator = $this->getFixturePath('iiif/validator.js', 'Finna');
        $result = $this->runExternalValidator([$validator], $manifestJson);
        if ($result->exitStatus !== 0) {
            $this->fail(
                "IIIF manifest generator validation failed for: $manifestJson" . PHP_EOL .
                'validator stdout: ' . $result->stdout . PHP_EOL .
                'validator stderr: ' . $result->stderr
            );
        }
    }

    #[TestWith([''])]
    #[TestWith(['[]'])]
    #[TestWith(["{'status': 500}"])]
    public function testExternalValidatorInvalidInput(string $input): void
    {
        $validator = $this->getFixturePath('iiif/validator.js', 'Finna');
        $result = $this->runExternalValidator([$validator], $input);
        $this->assertNotEquals(0, $result->exitStatus);
    }

    public static function getTestManifestGeneratorImageData(): \Generator
    {
        yield '[small, medium, large]' => [
            [
                [
                    [
                        'urls' => [
                            'small' => 'http://example.com/images/small.jpeg',
                            'medium' => 'http://example.com/images/medium.jpeg',
                            'large' => 'http://example.com/images/large.jpeg',
                        ],
                    ],
                ],
                'FooRecord',
                'solr',
                'https://example.com/Record/FooRecord/IIIFManifest',
                'Foo, a Fine Record',
            ],
            'array',
        ];
        yield '[small]' => [
            [
                [
                    [
                        'urls' => [
                            'small' => 'http://example.com/images/small.jpeg',
                        ],
                    ],
                ],
                'FooRecord',
                'solr',
                'https://example.com/Record/FooRecord/IIIFManifest',
                'Foo, a Fine Record',
            ],
            'array',
        ];
        yield '[small, large]' => [
            [
                [
                    [
                        'urls' => [
                            'small' => 'http://example.com/images/small.jpeg',
                            'large' => 'http://example.com/images/large.jpeg',
                        ],
                    ],
                ],
                'FooRecord',
                'solr',
                'https://example.com/Record/FooRecord/IIIFManifest',
                'Foo, a Fine Record',
            ],
            'array',
        ];
        yield '[medium]' => [
            [
                [
                    [
                        'urls' => [
                            'medium' => 'http://example.com/images/medium.jpeg',
                        ],
                    ],
                ],
                'FooRecord',
                'solr',
                'https://example.com/Record/FooRecord/IIIFManifest',
                'Foo, a Fine Record',
            ],
            'array',
        ];
        yield 'multiple images' => [
            [
                [
                    [
                        'urls' => [
                            'medium' => 'http://example.com/images/1/medium.jpeg',
                        ],
                    ],
                    [
                        'urls' => [
                            'large' => 'http://example.com/images/2/large.jpeg',
                        ],
                    ],
                    [
                        'urls' => [
                            'small' => 'http://example.com/images/3/small.jpeg',
                            'medium' => 'http://example.com/images/3/medium.jpeg',
                            'large' => 'http://example.com/images/3/large.jpeg',
                        ],
                    ],
                    [
                        'urls' => [
                            'medium' => 'http://example.com/images/4/medium.jpeg',
                        ],
                    ],
                ],
                'FooRecord',
                'solr',
                'https://example.com/Record/FooRecord/IIIFManifest',
                'Foo, a Fine Record',
            ],
            'array',
        ];
    }
}

/**
 * Output of one external validator output
 */
class ExternalValidatorOutput
{
    public function __construct(
        public int $exitStatus,
        public string $stdout,
        public string $stderr
    ) {
    }
}
