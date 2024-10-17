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
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setHelp('Fixes variable declarations in SCSS files.')
            ->addOption(
                'overrides_file',
                null,
                InputOption::VALUE_REQUIRED,
                'File for SCSS variable overrides'
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
                'Name of main scss file to use as an entry point'
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
        return Command::SUCCESS;
    }

    /**
     * Process a file
     *
     * @param string          $fileName File name
     * @param array           $vars     Currently defined variables
     * @param OutputInterface $output   Output object
     * @param bool            $discover Whether to just discover variable
     * @param bool            $write    Whether to write changes
     *
     * @return bool
     */
    protected function processFile(string $fileName, array &$vars, bool $discover, bool $write): bool
    {
        if (!$this->isReadableFile($fileName)) {
            $this->error("File $fileName does not exist or is not a readable file");
            return false;
        }
        $fileDir = dirname($fileName);
        $lineNo = 0;
        $this->debug(
            "Start processing $fileName" . ($write ? '' : ' (read only)'),
            $write ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_DEBUG
        );
        $lines = file($fileName);
        $inMixin = 0;
        $requiredVars = [];
        foreach ($lines as $idx => $line) {
            ++$lineNo;
            $parts = explode('//', $line, 2);
            $line = $parts[0];
            $comments = $parts[1] ?? null;

            // variable declaration
            if (preg_match('/^\s*\$(' . static::VARIABLE_CHARS . '+):\s*(.*?);?$/', $line, $matches)) {
                [, $var, $value] = $matches;
                $value = preg_replace('/\s*!default\s*;?\s*$/', '', $value);
                if (array_key_exists($var, $vars)) {
                    $this->debug(
                        "$fileName:$lineNo: $var: '$value' overrides existing value '" . $vars[$var] . "'",
                        OutputInterface::VERBOSITY_DEBUG
                    );
                } else {
                    $this->debug("$fileName:$lineNo: found '$var': '$value'", OutputInterface::VERBOSITY_DEBUG);
                }
                $vars[$var] = $value;
            // @import
            } elseif (preg_match('/^\s*@import\s+"([^"]+)"\s*;/', $line, $matches)) {
                $import = $matches[1];
                if (!($pathInfo = $this->resolveImportFileName($import, $fileDir))) {
                    $this->error("$fileName:$lineNo: import file $import not found");
                    return false;
                } else {
                    $this->debug(
                        "$fileName:$lineNo: import $pathInfo[fullPath] as $import"
                        . ($pathInfo['inBaseDir'] ? ' (IN BASE)' : ''),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    if (!$this->processFile($pathInfo['fullPath'], $vars, $discover, $pathInfo['inBaseDir'])) {
                        return false;
                    }
                }
            }

            if ($discover || !$write) {
                continue;
            }

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

            // Collect variables that need to be defined:
            if ($newVars = $this->checkVariables("$fileName:$lineNo", $line, $vars)) {
                $requiredVars = [
                    ...$requiredVars,
                    ...$newVars
                ];
            }
            $lines[$idx] = $line . ($comments ? "//$comments" : '');
        }
        if (!$discover && $write) {
            // Prepend required variables:
            if ($requiredVars) {
                $linesToAdd = [
                    '// The following variables were automatically added in SCSS conversion' . PHP_EOL
                ];
                $addedVars = [];
                foreach (array_reverse($requiredVars) as $current) {
                    $var = $current['var'];
                    if (!in_array($var, $addedVars)) {
                        $value = $current['value'];
                        $linesToAdd[] = "\$$var: $value;" . PHP_EOL;
                        $addedVars[] = $var;
                    }
                }
                $linesToAdd[] = PHP_EOL;
                array_unshift($lines, ...$linesToAdd);
            }
            // Write the updated file:
            file_put_contents($fileName, implode('', $lines));
        }
        return true;
    }

    /**
     * Replace variables that are defined later with their last values
     *
     * @param string $line Line
     * @param array  $vars Currently defined variables
     *
     * @return ?array Array of required variables and their valuesm, or null on error
     */
    protected function checkVariables(string $lineId, string $line, array $vars): ?array
    {
        $ok = true;
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
                        $this->debug("$lineId: $var ok", OutputInterface::VERBOSITY_VERBOSE);
                        return $matches[0];
                    }
                    if (null === $lastVal) {
                        $this->error("$lineId: Value for variable '$var' not found");
                        $ok = false;
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
        } while ($ok && $lastLine !== $line);
        return $ok ? $required : null;
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
     * @param string $fileName Relative file name
     * @param string $baseDir  Base directory
     *
     * @return ?array
     */
    protected function resolveImportFileName(string $fileName, string $baseDir): ?array
    {
        if (!str_ends_with($fileName, '.scss')) {
            $fileName .= '.scss';
        }
        $allDirs = [
            $baseDir,
            ...$this->includePaths
        ];
        foreach ($allDirs as $dir) {
            // full import
            $fullPath = "$dir/$fileName";
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
     * Check if file name points to a readable file
     *
     * @param string $fileName File name
     *
     * @return bool
     */
    protected function isReadableFile(string $fileName): bool
    {
        return file_exists($fileName) && (is_file($fileName) || is_link($fileName));
    }
}
