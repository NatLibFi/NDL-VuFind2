<?php

/**
 * Console command: fix SCSS variable declarations.
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
 * @package  Console
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace FinnaConsole\Command\Util;

use PHPMD\Console\Output;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command: fix SCSS variable declarations.
 *
 * @category VuFind
 * @package  Console
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
#[AsCommand(
    name: 'util/scssFixer',
    description: 'SCSS fixer'
)]
class ScssFixerCommand extends Command
{
    const VARIABLE_CHARS = '[a-zA-Z_-]';

    /**
     * Include paths
     *
     * @var array
     */
    protected $includePaths = [];

    /**
     * Console output
     *
     * @var OutputInterface
     */
    protected $output = null;

    /**
     * All variables with the last occurrence taking precedence (like in lesscss)
     *
     * @var array
     */
    protected $allVars = [];

    /**
     * Base dir for the main SCSS file
     *
     * @var string
     */
    protected $scssBaseDir = '';

    /**
     * An array tracking all processed files
     *
     * @var array
     */
    protected $allFiles = [];

    /**
     * File to use for all added variables
     *
     * @var ?string
     */
    protected $variablesFile = null;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setHelp('Fixes variable declarations in SCSS files.')
            ->addOption(
                'variables_file',
                null,
                InputOption::VALUE_REQUIRED,
                'File to use for added SCSS variables'
            )
            ->addOption(
                'include_path',
                'I',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Include directories'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Files not to be touched (in addition to the ones outside of the starting directory)'
            )
            ->addArgument(
                'scss_file',
                InputArgument::REQUIRED,
                'Name of main SCSS file to use as an entry point'
            );
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->variablesFile = $input->getOption('variables_file');
        $this->includePaths = $input->getOption('include_path');
        $this->output = $output;
        $mainFile = $input->getArgument('scss_file');
        $this->allVars = [];
        $this->scssBaseDir = realpath(dirname($mainFile));
        // First read all vars:
        if (!$this->processFile($mainFile, $this->allVars, true, false)) {
            return Command::FAILURE;
        }
        // Now do changes:
        $currentVars = [];
        if (!$this->processFile($mainFile, $currentVars, false, true)) {
            $this->error('Stop on failure');
            return Command::FAILURE;
        }

        // Write out the modified files:
        if (!$this->updateModifiedFiles()) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Process a file
     *
     * @param string          $filename  File name
     * @param array           $vars      Currently defined variables
     * @param OutputInterface $output    Output object
     * @param bool            $discover  Whether to just discover files and their content
     * @param bool            $change    Whether to do changes to the file
     *
     * @return bool
     */
    protected function processFile(
        string $filename,
        array &$vars,
        bool $discover,
        bool $change
    ): bool {
        if (!$this->isReadableFile($filename)) {
            $this->error("File $filename does not exist or is not a readable file");
            return false;
        }
        $filename = str_starts_with($filename, '/') ? $filename : realpath($filename);
        $fileDir = dirname($filename);
        $lineNo = 0;
        $this->debug(
            "Start processing $filename" . ($discover ? ' (discovery)' : ($change ? '' : ' (read only)')),
            $change ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_DEBUG
        );
        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        $this->updateFileCollection($filename, compact('lines'));

        // Process string substitutions
        if ($change) {
            $this->processSubstitutions($filename, $lines);
            $this->updateFileCollection($filename, compact('lines'));
        }

        $this->updateFileCollection($filename, compact('lines'));

        $inMixin = 0;
        $requiredVars = [];
        foreach ($lines as $idx => $line) {
            ++$lineNo;
            $lineId = "$filename:$lineNo";
            $parts = explode('//', $line, 2);
            $line = $parts[0];
            $comments = $parts[1] ?? null;

            if (str_starts_with(trim($line), '@mixin ')) {
                $inMixin = $this->getBlockLevelChange($line);
                continue;
            }
            if ($inMixin) {
                $inMixin += $this->getBlockLevelChange($line);
            }

            if ($inMixin) {
                continue;
            }

            // Process variable declarations:
            $this->processVariables($lineId, $line, $vars);
            // Process import:
            if (!$this->processImport($lineId, $fileDir, $line, $vars, $discover)) {
                return false;
            }

            if ($discover || !$change) {
                continue;
            }

            // Collect variables that need to be defined:
            if ($newVars = $this->checkVariables($lineId, $line, $vars)) {
                $requiredVars = [
                    ...$requiredVars,
                    ...$newVars
                ];
            }
            $lines[$idx] = $line . ($comments ? "//$comments" : '');
        }

        if (!$discover && $change && $requiredVars || $this->allFiles[$filename]['lines'] !== $lines) {
            $this->allFiles[$filename]['modified'] = true;
            $this->allFiles[$filename]['lines'] = $lines;
            $this->allFiles[$filename]['requiredVars'] = array_merge(
                $this->allFiles[$filename]['requiredVars'],
                $requiredVars
            );
        }

        return true;
    }


    /**
     * Find variables
     *
     * @param string $lineId Line identifier for logging
     * @param string $line   Line
     * @param array  $vars   Currently defined variables
     *
     * @return ?array Array of required variables and their valuesm, or null on error
     */
    protected function processVariables(string $lineId, string $line, array &$vars): void
    {
        if (!preg_match('/^\s*\$(' . static::VARIABLE_CHARS . '+):\s*(.*?);?$/', $line, $matches)) {
            return;
        }
        [, $var, $value] = $matches;
        $value = preg_replace('/\s*!default\s*;?\s*$/', '', $value);
        if (array_key_exists($var, $vars)) {
            $this->debug(
                "$lineId: $var: '$value' overrides existing value '" . $vars[$var] . "'",
                OutputInterface::VERBOSITY_DEBUG
            );
        } else {
            $this->debug("$lineId: found '$var': '$value'", OutputInterface::VERBOSITY_DEBUG);
        }
        $vars[$var] = $value;
    }

    /**
     * Process @import
     *
     * @param string $lineId   Line identifier for logging
     * @param string $fileDir  Current file directory
     * @param string $line     Line
     * @param array  $vars     Currently defined variables
     * @param bool   $discover Whether to just discover files and their content
     *
     * @return bool
     */
    protected function processImport(string $lineId, string $fileDir, string $line, array &$vars, bool $discover): bool
    {
        if (!preg_match("/^\s*@import\s+['\"]([^'\"]+)['\"]\s*;/", $line, $matches)) {
            // Check for LESS import reference:
            if (!preg_match("/^\s*@import \/\*\(reference\)\*\/ ['\"]([^'\"]+)['\"]\s*;/", $line, $matches)) {
                return true;
            }
        }
        $import = $matches[1];
        if (str_ends_with($import, '.css')) {
            $this->debug("$lineId: skipping .css import");
            return true;
        }
        if (!($pathInfo = $this->resolveImportFileName($import, $fileDir))) {
            $this->error("$lineId: import file $import not found");
            return false;
        } else {
            $this->debug(
                "$lineId: import $pathInfo[fullPath] as $import" . ($pathInfo['inBaseDir'] ? ' (IN BASE)' : ''),
                OutputInterface::VERBOSITY_DEBUG
            );
            if (!$this->processFile($pathInfo['fullPath'], $vars, $discover, $pathInfo['inBaseDir'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Replace variables that are defined later with their last values
     *
     * @param string $lineId Line identifier for logging
     * @param string $line   Line
     * @param array  $vars   Currently defined variables
     *
     * @return ?array Array of required variables and their values, or null on error
     */
    protected function checkVariables(string $lineId, string $line, array $vars): ?array
    {
        $required = [];
        do {
            $lastLine = $line;
            $line = preg_replace_callback(
                '/\$(' . static::VARIABLE_CHARS . '+)(?!.*:)\\b/',
                function ($matches) use ($vars, $lineId, &$ok, &$required) {
                    $var = $matches[1];
                    $lastVal = $this->allVars[$var] ?? null;
                    if (isset($vars[$var]) && $vars[$var] === $lastVal) {
                        // Previous definition contains the correct value, return as is:
                        $this->debug("$lineId: $var ok", OutputInterface::VERBOSITY_VERY_VERBOSE);
                        return $matches[0];
                    }
                    if (null === $lastVal) {
                        $this->warning("$lineId: Value for variable '$var' not found");
                        return $matches[0];
                    }
                    // Use last defined value:
                    $this->debug("$lineId: Need $lastVal for $var");
                    $required[] = [
                        'var' => $var,
                        'value' => $lastVal,
                    ];
                    return $lastVal;
                },
                $line
            );
        } while ($lastLine !== $line);
        return $required;
    }

    /**
     * Get block level (depth) change
     *
     * @param string $line Line
     *
     * @return int
     */
    protected function getBlockLevelChange(string $line): int
    {
        $level = 0;
        foreach (str_split($line) as $ch) {
            if ('{' === $ch) {
                ++$level;
            } elseif ('}' === $ch) {
                --$level;
            }
        }
        return $level;
    }

    /**
     * Find import file
     *
     * @param string $filename Relative file name
     * @param string $baseDir  Base directory
     *
     * @return ?array
     */
    protected function resolveImportFileName(string $filename, string $baseDir): ?array
    {
        if (!str_ends_with($filename, '.scss')) {
            $filename .= '.scss';
        }
        $allDirs = [
            $baseDir,
            ...$this->includePaths
        ];
        foreach ($allDirs as $dir) {
            // full import
            $fullPath = "$dir/$filename";
            if (!$this->isReadableFile($fullPath)) {
                // reference import
                $fullPath = dirname($fullPath) . '/_' . basename($fullPath);
            }
            if ($this->isReadableFile($fullPath)) {
                return [
                    'fullPath' => $fullPath,
                    'inBaseDir' => str_starts_with(realpath($fullPath), $this->scssBaseDir . '/'),
                ];
            }
        }
        return null;
    }

    /**
     * Update any modified files
     *
     * @return bool
     */
    protected function updateModifiedFiles(): bool
    {
        // If we have a variables file, collect all variables needed by later files and add them:
        if ($this->variablesFile) {
            $variablesFile = realpath($this->variablesFile) ?: $this->variablesFile;
            $variablesFileIndex = $this->allFiles[$variablesFile]['index'] ?? PHP_INT_MAX;

            $allRequiredVars = [];
            foreach ($this->allFiles as $filename => &$fileSpec) {
                // Check if the file is included before the variables file (if so, we must add the variables in
                // that file):
                if ($fileSpec['index'] < $variablesFileIndex) {
                    continue;
                }
                array_push($allRequiredVars, ...$fileSpec['requiredVars']);
                $fileSpec['requiredVars'] = [];
            }
            unset($fileSpec);
            $this->updateFileCollection(
                $variablesFile,
                [
                    'requiredVars' => $allRequiredVars,
                    'modified' => true,
                ]
            );
            $this->debug(count($allRequiredVars) . " variables added to $variablesFile");
        }

        foreach ($this->allFiles as $filename => $fileSpec) {
            if (!$fileSpec['modified'] && !$fileSpec['requiredVars']) {
                continue;
            }
            $lines = $fileSpec['lines'];

            // Prepend required variables:
            if ($fileSpec['requiredVars']) {
                $linesToAdd = ['// The following variables were automatically added in SCSS conversion'];
                $addedVars = [];
                foreach (array_reverse($fileSpec['requiredVars']) as $current) {
                    $var = $current['var'];
                    if (!in_array($var, $addedVars)) {
                        $value = $current['value'];
                        $linesToAdd[] = "\$$var: $value;";
                        $addedVars[] = $var;
                    }
                }
                $linesToAdd[] = '';
                array_unshift($lines, ...$linesToAdd);
            }
            // Write the updated file:
            if (false === file_put_contents($filename, implode(PHP_EOL, $lines))) {
                $this->error("Could not write file $filename");
            }
            $this->debug("$filename updated");
        }

        return true;
    }

    /**
     * Update a file in the all files collection
     *
     * @param string $filename File name
     * @param array  $values   Values to set
     *
     * @return void;
     */
    protected function updateFileCollection(string $filename, array $values): void
    {
        if (null === ($oldValues = $this->allFiles[$filename] ?? null)) {
            $oldValues = [
                'modified' => false,
                'requiredVars' => [],
            ];
            $values['index'] = count($this->allFiles);
        }
        if (!isset($oldValues['lines']) && !isset($values['lines'])) {
            // Read in any existing file:
            if (file_exists($filename)) {
                if (!$this->isReadableFile($filename)) {
                    throw new \Exception("$filename is not readable");
                }
                $values['lines'] = file($filename, FILE_IGNORE_NEW_LINES);
            }
        }
        // Set modified flag if needed:
        if (isset($oldValues['lines']) && isset($values['lines']) && $oldValues['lines'] !== $values['lines']) {
            $values['modified'] = true;
        }
        $this->allFiles[$filename] = array_merge($oldValues, $values);
    }

    /**
     * Process string substitutions
     *
     * @param string $filename File name
     * @param array  $lines    File contents
     */
    protected function processSubstitutions(string $filename, array &$lines): void
    {
        $this->debug("$filename: start processing substitutions", OutputInterface::VERBOSITY_DEBUG);
        $contents = implode(PHP_EOL, $lines);
        foreach ($this->getSubstitutions() as $i => $substitution) {
            $this->debug("$filename: processing substitution $i", OutputInterface::VERBOSITY_DEBUG);
            if (str_starts_with($substitution['pattern'], '/')) {
                // Regexp
                if (is_string($substitution['replacement'])) {
                    $contents = preg_replace($substitution['pattern'], $substitution['replacement'], $contents);
                } else {
                    $contents = preg_replace_callback(
                        $substitution['pattern'],
                        $substitution['replacement'],
                        $contents
                    );
                }
                if (null === $contents) {
                    throw new \Exception(
                        "Failed to process regexp substitution $i: " . $substitution['pattern']
                        . ': ' . preg_last_error_msg()
                    );
                }
            } else {
                // String
                $contents = str_replace($substitution['pattern'], $substitution['replacement'], $contents);
            }
        }

        $lines = explode(PHP_EOL, $contents);
        $this->debug("$filename: done processing substitutions", OutputInterface::VERBOSITY_DEBUG);
    }

    /**
     * Get substitutions
     *
     * @return array;
     */
    protected function getSubstitutions(): array
    {
        return [
            [ // Revert invalid @ => $ changes for css rules:
                'pattern' => '/\$(supports|container) \(/i',
                'replacement' => '@$1 (',
            ],
            [ // Revert @if => $if change:
                'pattern' => '/\$if \(/i',
                'replacement' => '@if (',
            ],
            [ // Revert @use => $use change:
                'pattern' => "/\$use '/i",
                'replacement' => "@use '",
            ],
            [ // Revert @supports => $supports change:
                'pattern' => "/\$supports '/i",
                'replacement' => "@supports '",
            ],
            [ // Revert @page => $page change:
                'pattern' => '$page ',
                'replacement' => "@page ",
            ],
            [ // Fix comparison:
                'pattern' => '/ ==< /i',
                'replacement' => ' <= ',
            ],
            [ // Remove !important from variables:
                'pattern' => '/^[^(]*(\$.+?):(.+?)\s*!important\s*;/m',
                'replacement' => '$1:$2;',
            ],
            [ // Remove !important from functions:
                'pattern' => '/^[^(]*(\$.+?):(.+?)\s*!important\s*\)/m',
                'replacement' => '$1:$2;',
            ],
            [ // fadein => fade-in:
                'pattern' => '/fadein\((\S+),\s*(\S+)\)/',
                'replacement' => function ($matches) {
                    return 'fade-in(' . $matches[1] . ', ' . (str_replace('%', '', $matches[2]) / 100) . ')';
                },
            ],
            [ // fadeout => fade-out:
                'pattern' => '/fadeout\((\S+),\s*(\S+)\)/',
                'replacement' => function ($matches) {
                    return 'fade-out(' . $matches[1] . ', ' . (str_replace('%', '', $matches[2]) / 100) . ')';
                },
            ],
            [ // replace invalid characters in variable names:
                'pattern' => '/\$([^: };\/]+)/',
                'replacement' => function ($matches) {
                    return '$' . str_replace('.', '__', $matches[1]);
                },
            ],
            [ // remove invalid &:
                'pattern' => '/([a-zA-Z])&:/',
                'replacement' => '$1:',
            ],
            [ // remove (reference) from import):
                'pattern' => '/@import\s+\(reference\)\s*/',
                'replacement' => '@import /*(reference)*/ ',
            ],
            [ // fix missing semicolon from background-image rule:
                'pattern' => '/(\$background-image:([^;]+?))\n/',
                'replacement' => '$1;\n',
            ],
            [ // remove broken (and useless) rule:
                'pattern' => '/\.feed-container \.list-feed \@include feed-header\(\);/',
                'replacement' => '',
            ],
            [ // interpolate variables in media queries:
                'pattern' => '/\@media (\$[^ ]+)/',
                'replacement' => '@media #{$1}',
            ],
            [ // missing semicolon:
                'pattern' => '/(.+:.*auto)\n/',
                'replacement' => "$1;\n",
            ],
            [ // lost space in mixin declarations:
                'pattern' => '/(\@mixin.+){/',
                'replacement' => '$1 {',
            ],
            [ // special cases: media query variables
                'pattern' => '/(\$(mobile-portrait|mobile|tablet|desktop):\s*)(.*?);/s',
                'replacement' => '$1"$2";',
            ],
            [ // special cases: mobile mixin
                'pattern' => '/\.mobile\(\{(.*?)\}\);/s',
                'replacement' => '@media #{$mobile} { & { $1 } }',
            ],
            [ // special cases: mobile mixin 2
                'pattern' => '@mixin mobile($rules){',
                'replacement' => '@mixin mobile {',
            ],
            [ // special cases: mobile mixin 3
                'pattern' => '$rules();',
                'replacement' => '@content;',
            ],
            [ // invalid mixin name
                'pattern' => 'text(uppercase)',
                'replacement' => 'text-uppercase',
            ],
            [ // when isnumber
                'pattern' => '& when (isnumber($z-index))',
                'replacement' => '@if $z-index != null',
            ],
            [ // blocks extending container
                'pattern' => '@include container();',
                'replacement' => '@extend .container;',
            ],
            [ // blocks extending more-link
                'pattern' => '@include more-link();',
                'replacement' => '@extend .more-link;',
            ],
            [ // fix math operations
                'pattern' => '/(\s+)(\(.+\/.+\))/',
                'replacement' => '$1calc$2',
            ],
            [ // typo
                'pattern' => '$carousel-header-color none;',
                'replacement' => '$carousel-header-color: none;',
            ],
            [ // typo
                'pattern' => '$brand-primary // $link-color;',
                'replacement' => '$brand-primary; // $link-color',
            ],
            [ // typo
                'pattern' => '- aukioloaikojen otsikko',
                'replacement' => '{ /* aukioloaikojen otsikko */ }',
            ],
            [ // typo
                'pattern' => '$link-hover-color: $tut-a-hover,',
                'replacement' => '$link-hover-color: $tut-a-hover;',
            ],
            [ // math without calc
                'pattern' => '/(.*\s)(\S+ \/ (\$|\d)[^\s;]*)/',
                'replacement' => function ($matches) {
                    [$full, $pre, $math] = $matches;
                    if (str_contains($matches[1], '(')) {
                        return $full;
                    }
                    return $pre . "calc($math)";
                },
            ],
        ];
    }

    /**
     * Output a debug message
     *
     * @param string $msg       Message
     * @param int    $verbosity Verbosity level
     *
     * @return void
     */
    protected function debug(string $msg, int $verbosity = OutputInterface::VERBOSITY_VERBOSE): void
    {
        $this->output->writeln($msg, $verbosity);
    }

    /**
     * Output an error message
     *
     * @param string $msg Message
     *
     * @return void
     */
    protected function error(string $msg): void
    {
        if ($this->output) {
            $this->output->writeln('<error>' . OutputFormatter::escape($msg) . '</error>');
        }
    }

    /**
     * Output a warning message
     *
     * @param string $msg Message
     *
     * @return void
     */
    protected function warning(string $msg): void
    {
        if ($this->output) {
            $this->output->writeln('<comment>' . OutputFormatter::escape($msg) . '</comment>');
        }
    }

    /**
     * Check if file name points to a readable file
     *
     * @param string $filename File name
     *
     * @return bool
     */
    protected function isReadableFile(string $filename): bool
    {
        return file_exists($filename) && (is_file($filename) || is_link($filename));
    }
}
