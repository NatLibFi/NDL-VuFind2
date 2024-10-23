<?php

/**
 * Console command: convert style files from LESS to SCSS (Sass).
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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Config\PathResolver;

/**
 * Console command: convert style files from LESS to SCSS (Sass).
 *
 * @category VuFind
 * @package  Console
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
#[AsCommand(
    name: 'util/lessToScss',
    description: 'LESS to SCSS conversion'
)]
class LessToScssCommand extends Command
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
    protected $allLessVars = [];

    /**
     * Source directory (LESS)
     *
     * @var string
     */
    protected $sourceDir = '';

    /**
     * Target directory (SCSS)
     *
     * @var string
     */
    protected $targetDir = '';

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
     * Files excluded from output
     *
     * @var array
     */
    protected $excludedFiles = [];

    /**
     * Constructor
     *
     * @param PathResolver $pathResolver Config path resolver
     */
    public function __construct(protected PathResolver $pathResolver)
    {
        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setHelp('Converts LESS styles to SCSS')
            ->addOption(
                'variables_file',
                null,
                InputOption::VALUE_REQUIRED,
                'File to use for added SCSS variables (may be relative to '
            )
            ->addOption(
                'include_path',
                'I',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Include directories for SCSS parser'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Files not to be touched in the target directory (in addition to the ones outside of the starting'
                . ' directory)'
            )
            ->addArgument(
                'main_file',
                InputArgument::REQUIRED,
                'Main LESS file to use as entry point'
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
        $this->output = $output;
        $this->includePaths = $input->getOption('include_path');
        $mainFile = $input->getArgument('main_file');
        $this->sourceDir = dirname($mainFile);
        $this->targetDir = preg_replace('/\/less\b/', '/scss', $this->sourceDir);
        if ($this->excludedFiles = $input->getOption('exclude')) {
            $this->excludedFiles = array_map(
                function ($s) {
                    return $this->targetDir . '/' . $s;
                },
                $this->excludedFiles
            );
        }
        if ($variablesFile = $input->getOption('variables_file')) {
            $this->variablesFile = $this->sourceDir . '/' . preg_replace('/\.scss$/', '', $variablesFile);
        }
        // First read all vars:
        if (!$this->discoverLess($mainFile, $this->allLessVars)) {
            return Command::FAILURE;
        }
        // Now do changes:
        $currentVars = [];
        if (!$this->processFile($mainFile, $currentVars)) {
            $this->error('Stop on failure');
            return Command::FAILURE;
        }

        // Write out the target files:
        if (!$this->writeTargetFiles()) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Discover less variables
     *
     * @param string $filename File name
     * @param array  $vars     Currently defined variables
     *
     * @return bool
     */
    protected function discoverLess(string $filename, array &$vars): bool
    {
        if (!$this->isReadableFile($filename)) {
            $this->error("File $filename does not exist or is not a readable file");
            return false;
        }
        $fileDir = dirname($filename);
        $lineNo = 0;
        $this->debug("Start processing $filename (discovery)", OutputInterface::VERBOSITY_DEBUG);
        $lines = file($filename, FILE_IGNORE_NEW_LINES);

        $inMixin = 0;
        $inComment = false;
        foreach ($lines as $line) {
            ++$lineNo;
            if (trim($line) === '') {
                continue;
            }
            $lineId = "$filename:$lineNo";
            $parts = explode('//', $line, 2);
            $line = $parts[0];

            $cStart = strpos($line, '/*');
            $cEnd = strrpos($line, '*/');
            if (false !== $cStart && (false === $cEnd || $cEnd < $cStart)) {
                $inComment = true;
            } elseif (false !== $cEnd) {
                $inComment = false;
            }
            if ($inComment) {
                continue;
            }

            if (preg_match('/\.([\w\-]*)\s*\((.*)\)\s*\{/i', trim($line))) {
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
            $this->processLessVariables($lineId, $line, $vars);
            // Process import:
            if (!$this->processImports($lineId, $fileDir, $line, $vars, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Process a file
     *
     * @param string $filename File name
     * @param array  $vars     Currently defined variables
     *
     * @return bool
     */
    protected function processFile(string $filename, array &$vars): bool
    {
        if (!$this->isReadableFile($filename)) {
            $this->error("File $filename does not exist or is not a readable file");
            return false;
        }
        $fileDir = dirname($filename);
        $inSourceDir = $this->isInSourceDir($fileDir);
        $lineNo = 0;
        $this->debug("Start processing $filename (conversion)", OutputInterface::VERBOSITY_DEBUG);
        $lines = file($filename, FILE_IGNORE_NEW_LINES);

        // Process string substitutions
        if (!str_ends_with($filename, '.scss')) {
            $lines = explode(PHP_EOL, $this->processSubstitutions($filename, implode(PHP_EOL, $lines)));
            $this->updateFileCollection($filename, compact('lines', 'vars'));
        }

        $inMixin = 0;
        $inComment = false;
        $requiredVars = [];
        foreach ($lines as $idx => $line) {
            ++$lineNo;
            if (trim($line) === '') {
                continue;
            }
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

            $cStart = strpos($line, '/*');
            $cEnd = strrpos($line, '*/');
            if (false !== $cStart && (false === $cEnd || $cEnd < $cStart)) {
                $inComment = true;
            } elseif (false !== $cEnd) {
                $inComment = false;
            }
            if ($inComment) {
                continue;
            }

            // Process variable declarations:
            $this->processScssVariables($lineId, $line, $vars);
            // Process imports:
            if (!$this->processImports($lineId, $fileDir, $line, $vars, false)) {
                return false;
            }

            // Collect variables that need to be defined:
            if ($inSourceDir) {
                if ($newVars = $this->checkVariables($lineId, $line, $vars)) {
                    $requiredVars = [
                        ...$requiredVars,
                        ...$newVars
                    ];
                }
            }
            $lines[$idx] = $line . ($comments ? "//$comments" : '');
        }

        $this->updateFileCollection($filename, compact('lines', 'requiredVars'));

        return true;
    }

    /**
     * Check if path is in source directory
     *
     * @param string $path Path
     *
     * @return bool
     */
    protected function isInSourceDir(string $path): bool
    {
        return str_starts_with($path, $this->sourceDir) && !str_contains($path, '..');
    }

    /**
     * Check if path is in target directory
     *
     * @param string $path Path
     *
     * @return bool
     */
    protected function isInTargetDir(string $path): bool
    {
        return str_starts_with($path, $this->targetDir) && !str_contains($path, '..');
    }

    /**
     * Find variables in LESS
     *
     * @param string $lineId Line identifier for logging
     * @param string $line   Line
     * @param array  $vars   Currently defined variables
     *
     * @return ?array Array of required variables and their valuesm, or null on error
     */
    protected function processLessVariables(string $lineId, string $line, array &$vars): void
    {
        if (!preg_match('/^\s*\@(' . static::VARIABLE_CHARS . '+):\s*(.*?);?\s*$/', $line, $matches)) {
            return;
        }
        [, $var, $value] = $matches;
        $value = preg_replace('/\s*!default\s*;?\s*$/', '', $value);
        if (array_key_exists($var, $vars)) {
            $this->debug(
                "$lineId: '$var: $value' overrides existing value '" . $vars[$var] . "'",
                OutputInterface::VERBOSITY_DEBUG
            );
        } else {
            $this->debug("$lineId: found '$var: $value'", OutputInterface::VERBOSITY_DEBUG);
        }

        $vars[$var] = $value;
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
    protected function processScssVariables(string $lineId, string $line, array &$vars): void
    {
        if (!preg_match('/^\s*\$(' . static::VARIABLE_CHARS . '+):\s*(.*?);?$/', $line, $matches)) {
            return;
        }
        [, $var, $value] = $matches;
        $value = preg_replace('/\s*!default\s*;?\s*$/', '', $value, -1, $count);
        $default = $count > 0;
        $existing = $vars[$var] ?? null;
        if ($existing) {
            if ($existing['default'] && !$default) {
                $this->debug(
                    "$lineId: '$var: $value' overrides default value '" . $vars[$var]['value'] . "'",
                    OutputInterface::VERBOSITY_DEBUG
                );
            } else {
                return;
            }
        } else {
            $this->debug("$lineId: found '$var: $value'", OutputInterface::VERBOSITY_DEBUG);
        }
        $vars[$var] = compact('value', 'default');
    }

    /**
     * Process imports
     *
     * @param string $lineId   Line identifier for logging
     * @param string $fileDir  Current file directory
     * @param string $line     Line
     * @param array  $vars     Currently defined variables
     * @param bool   $discover Whether to just discover files and their content
     *
     * @return bool
     */
    protected function processImports(string $lineId, string $fileDir, string $line, array &$vars, bool $discover): bool
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
            $targetFileDir = str_replace($this->sourceDir, $this->targetDir, $fileDir);
            $targetFileDir = str_replace('/less', '/scss', $targetFileDir);
            $targetImport = str_replace('/less/', '/scss/', $import);
            if (!($pathInfo = $this->resolveImportFileName($targetImport, $targetFileDir))) {
                $this->error("$lineId: import file $import not found");
                return false;
            }
        } else {
            $this->debug(
                "$lineId: import $pathInfo[fullPath] as $import" . ($pathInfo['inBaseDir'] ? ' (IN BASE)' : ''),
                OutputInterface::VERBOSITY_DEBUG
            );
            if ($discover) {
                if (!$this->discoverLess($pathInfo['fullPath'], $vars)) {
                    return false;
                }
            } else {
                if (!$this->processFile($pathInfo['fullPath'], $vars, $discover, $pathInfo['inBaseDir'])) {
                    return false;
                }
            }
        }
        return true;
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
        $allDirs = [
            $baseDir,
            ...$this->includePaths
        ];
        $filename = preg_replace('/\.(less|scss)$/', '', $filename);
        foreach (['less', 'scss'] as $extension) {
            foreach ($allDirs as $dir) {
                // full path
                $fullPath = "$dir/$filename.$extension";
                if (!$this->isReadableFile($fullPath)) {
                    // reference import
                    $fullPath = dirname($fullPath) . '/_' . basename($fullPath);
                }
                if ($this->isReadableFile($fullPath)) {
                    return [
                        'fullPath' => $fullPath,
                        'inBaseDir' => str_starts_with(realpath($fullPath), $this->sourceDir . '/'),
                    ];
                }
            }
        }
        return null;
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
        preg_match_all('/\$(' . static::VARIABLE_CHARS . '+)(?!.*:)\\b/', $line, $allMatches);
        foreach ($allMatches[1] ?? [] as $var) {
            $lessVal = $this->allLessVars[$var] ?? null;
            if (
                isset($vars[$var])
                && null !== $lessVal
                && $vars[$var]['value'] === $this->processSubstitutions('', $lessVal)
            ) {
                // Previous definition contains the correct value:
                $this->debug("$lineId: $var ok", OutputInterface::VERBOSITY_VERY_VERBOSE);
                continue;
            }
            if (null === $lessVal) {
                $this->warning("$lineId: Value for variable '$var' not found");
                continue;
            }
            // Use last defined value:

            $this->debug("$lineId: Need $lessVal for $var");
            $required[] = [
                'var' => $var,
                'value' => $lessVal,
            ];
        }
        return $required;
    }

    /**
     * Resolve requirements for variables that depend on other variables
     *
     * @param array $vars      Variables to resolve
     * @param array $knownVars Vars that are already available
     *
     * @return array
     */
    protected function resolveVariableDependencies(array $vars, array $knownVars): array
    {
        $result = $vars;
        foreach ($vars as $current) {
            $var = $current['var'];
            $varDefinition = $current['value'];
            $loop = 0;
            while (preg_match('/[@\$](' . static::VARIABLE_CHARS . '+)/', $varDefinition, $matches)) {
                $requiredVar = $matches[1];
                if (in_array($requiredVar, $knownVars)) {
                    $this->debug(
                        "Existing definition found for '$requiredVar' required by '$var: $varDefinition'",
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    continue;
                }
                if ($requiredVarValue = $this->allLessVars[$requiredVar] ?? null) {
                    $this->debug("'$var: $varDefinition' requires '$requiredVar: $requiredVarValue'");
                    $result[] = [
                        'var' => $requiredVar,
                        'value' => $requiredVarValue,
                    ];
                    $varDefinition = $requiredVarValue;
                } else {
                    $this->warning(
                        "Could not resolve dependency for variable '$var'; definition missing for '$requiredVar'"
                    );
                    break;
                }
                if (++$loop >= 10) {
                    $this->warning("Value definition loop detected ($var -> $requiredVar)");
                    break;
                }
            }
        }
        return $result;
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
     * Update a file in the all files collection
     *
     * @param string $filename File name
     * @param array  $values   Values to set
     *
     * @return void;
     */
    protected function updateFileCollection(string $filename, array $values): void
    {
        $barename = preg_replace('/\.(less|scss)$/', '', $filename);
        if (null === ($oldValues = $this->allFiles[$barename] ?? null)) {
            $oldValues = [
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
        $this->allFiles[$barename] = array_merge($oldValues, $values);
    }

    /**
     * Write target files
     *
     * @return bool
     */
    protected function writeTargetFiles(): bool
    {
        // If we have a variables file, collect all variables needed by later files and add them:
        if ($this->variablesFile) {
            $variablesFileIndex = $this->allFiles[$this->variablesFile]['index'] ?? PHP_INT_MAX;

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
                $this->variablesFile,
                [
                    'requiredVars' => $allRequiredVars,
                ]
            );
            $this->debug(count($allRequiredVars) . " variables added to $this->variablesFile");
        }

        foreach ($this->allFiles as $filename => $fileSpec) {
            $fullPath = $this->getTargetFilename($filename);
            if (!$this->isInTargetDir($fullPath)) {
                continue;
            }
            $lines = $fileSpec['lines'] ?? [];

            // Prepend required variables:
            if ($fileSpec['requiredVars']) {
                $requiredVars = $this->resolveVariableDependencies($fileSpec['requiredVars'], $fileSpec['vars'] ?? []);
                $linesToAdd = ['// The following variables were automatically added in SCSS conversion'];
                $addedVars = [];
                foreach (array_reverse($requiredVars) as $current) {
                    $var = $current['var'];
                    if (!in_array($var, $addedVars)) {
                        $linesToAdd[] = $this->processSubstitutions('', "@$var: $current[value];");
                        $addedVars[] = $var;
                    }
                }

                // Remove later definitions for the required variables:
                foreach ($addedVars as $var) {
                    foreach ($lines as &$line) {
                        $line = preg_replace(
                            '/^(\s*\$' . preg_quote($var) . ':.*)$/',
                            '/* $1 // Commented out in SCSS conversion */',
                            $line
                        );
                    }
                    unset($line);
                }

                // Prepend new definitions:
                $linesToAdd[] = '';
                array_unshift($lines, ...$linesToAdd);
            }
            // Write the updated file:
            if (false === file_put_contents($fullPath, implode(PHP_EOL, $lines)) . PHP_EOL) {
                $this->error("Could not write file $fullPath");
            }
            $this->debug("$fullPath updated");
        }

        return true;
    }

    /**
     * Get target file name
     *
     * @param string $filename File name
     *
     * @return string
     */
    protected function getTargetFilename(string $filename): string
    {
        $fullPath = str_replace($this->sourceDir, $this->targetDir, $filename);
        if (!str_ends_with($fullPath, '.scss')) {
            $fullPath .= '.scss';
        }
        return $fullPath;
    }

    /**
     * Process string substitutions
     *
     * @param string $filename File name
     * @param string $contents File contents
     *
     * @return string
     */
    protected function processSubstitutions(string $filename, string $contents): string
    {
        if ($filename) {
            $this->debug("$filename: start processing substitutions", OutputInterface::VERBOSITY_DEBUG);
        }
        foreach ($this->getSubstitutions() as $i => $substitution) {
            if ($filename) {
                $this->debug("$filename: processing substitution $i", OutputInterface::VERBOSITY_DEBUG);
            }
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

        if ($filename) {
            $this->debug("$filename: done processing substitutions", OutputInterface::VERBOSITY_DEBUG);
        }

        return $contents;
    }

    /**
     * Get substitutions
     *
     * @return array;
     */
    protected function getSubstitutions(): array
    {
        if ($localConfigFile = $this->pathResolver->getLocalConfigPath('lessToScss.config.php')) {
            $this->debug("Using local config file $localConfigFile", OutputInterface::VERBOSITY_DEBUG);
            $config = include $localConfigFile;
        } else {
            $configFile = dirname(__FILE__) . '/../../../../config/lessToScss.config.php';
            $this->debug("Using shared config file $configFile", OutputInterface::VERBOSITY_DEBUG);
            $config = include $configFile;
        }
        return $config;
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
