<?php

/**
 * XML reader
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Record
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

declare(strict_types=1);

namespace Finna\Record\XML;

use function is_array;

/**
 * XML reader
 *
 * @category VuFind
 * @package  Record
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class XmlReader
{
    /**
     * Parsed XML
     *
     * @var array
     */
    protected array $parsed = [];

    /**
     * Default namespace URI for path parts without namespace
     *
     * @var ?string
     */
    protected ?string $defaultNamespace = null;

    /**
     * Parse an XML string.
     *
     * @param string $xml XML
     *
     * @return static
     */
    public function parse(string $xml): static
    {
        $this->parsed = (new XmlParser())->parse($xml);
        return $this;
    }

    /**
     * Set default namespace for path queries.
     *
     * @param ?string $namespace Namespace URI, or null for no default
     *
     * @return static
     */
    public function setDefaultNamespace(?string $namespace): static
    {
        $this->defaultNamespace = $namespace;
        return $this;
    }

    /**
     * Get all nodes by path starting from the given single node.
     *
     * @param ?array       $node Node to start from (optional)
     * @param string|array $path Path (array or a slash-delimited string) with each node either in Clark notation, or
     * just node name with $this->defaultNamespace defined
     *
     * @return array[]
     */
    public function all(?array $node = null, string|array $path = ''): array
    {
        return $this->allByPath($node, $path);
    }

    /**
     * Get first node by path.
     *
     * @param ?array       $node Node to start from (optional)
     * @param string|array $path Path (array or a slash-delimited string) with each node either in Clark notation, or
     * just node name with $this->defaultNamespace defined
     *
     * @return ?array
     */
    public function first(?array $node = null, string|array $path = ''): ?array
    {
        return $this->all($node, $path)[0] ?? null;
    }

    /**
     * Get all node values by path starting from the given single node.
     *
     * @param ?array       $node        Node to start from (optional)
     * @param string|array $path        Path (array or a slash-delimited string) with each node either in Clark
     * notation, or just node name with $this->defaultNamespace defined
     * @param bool         $trim        Trim results?
     * @param bool         $emptyValues Include empty values?
     *
     * @return string[]
     */
    public function allValues(
        ?array $node = null,
        string|array $path = '',
        bool $trim = true,
        bool $emptyValues = false
    ): array {
        $results = $this->getValues($this->all($node, $path));
        if (!$emptyValues) {
            $results = array_values(array_filter($results, fn ($s) => '' !== $s));
        }
        return $trim ? array_map('trim', $results) : $results;
    }

    /**
     * Get first node value as string by path.
     *
     * @param ?array       $node Node to start from (optional)
     * @param string|array $path Path (array or a slash-delimited string) with each node either in Clark notation, or
     * just node name with $this->defaultNamespace defined
     * @param bool         $trim Trim result?
     *
     * @return ?string
     */
    public function firstValue(?array $node = null, string|array $path = '', bool $trim = true): ?string
    {
        $first = $this->first($node, $path);
        $result = $first['val'] ?? null;
        return ($trim && null !== $result) ? trim($result) : $result;
    }

    /**
     * Get attribute from a node
     *
     * @param ?array $node Node
     * @param string $attr Attribute either in Clark notation, or just name with $this->defaultNamespace defined
     * @param bool   $trim Trim result?
     *
     * @return ?string
     */
    public function attr(?array $node, string $attr, bool $trim = true): ?string
    {
        // Try to find the attribute first with namespace and fall back to search without namespace:

        $result = $node['attrs'][$this->clarkify($attr)]
            ?? $node['attrs'][$attr]
            ?? null;
        return ($trim && null !== $result) ? trim($result) : $result;
    }

    /**
     * Get the string value of a node
     *
     * @param array $node Node
     * @param bool  $trim Trim result?
     *
     * @return string
     */
    public function value(array $node, bool $trim = true): string
    {
        return $trim ? trim($node['val']) : $node['val'];
    }

    /**
     * Recursively traverse all branches by path and return any values found.
     *
     * @param ?array       $root Node to start from
     * @param string|array $path Path (array or a slash-delimited string) with each node either in Clark notation
     * just node name with $this->defaultNamespace defined
     *
     * @return array
     */
    protected function allByPath(?array $root, string|array $path): array
    {
        $currentNodes = $root['sub'] ?? $this->parsed['sub'];
        $remainingPath = is_array($path) ? $path : explode('/', $path);
        $pathPart = array_shift($remainingPath);

        // Verify that the path part has namespace:
        $pathPart = $this->clarkify($pathPart);

        // Try to find nodes first with namespace and fall back to search without namespace:
        foreach ([false, true] as $fallback) {
            if ($fallback) {
                $clark = $this->parseClark($pathPart);
                $pathPart = '{}' . $clark[1];
            }
            $result = null;
            foreach ($currentNodes as $node) {
                if ($pathPart === $node['name']) {
                    if ($remainingPath) {
                        if ($node['sub']) {
                            $result = [
                                ...($result ?? []),
                                ...$this->allByPath($node, $remainingPath),
                            ];
                        }
                    } else {
                        $result[] = $node;
                    }
                }
            }
            if (null !== $result) {
                return $result;
            }
        }

        return [];
    }

    /**
     * Get values from an array of nodes
     *
     * @param array $nodes Nodes
     *
     * @return string[]
     */
    protected function getValues(array $nodes): array
    {
        return array_map(
            function ($node): string {
                return $node['val'];
            },
            $nodes
        );
    }

    /**
     * Ensure a node or attribute name is in Clark notation
     *
     * @param string $name Name
     *
     * @return string
     */
    protected function clarkify(string $name): string
    {
        // Assume correct notation if it starts with a curly bracket:
        if (str_starts_with($name, '{')) {
            return $name;
        }
        if (null === $this->defaultNamespace) {
            throw new \InvalidArgumentException(
                "'$name' must use Clark notation, or default namespace must be defined"
            );
        }
        return '{' . $this->defaultNamespace . '}' . $name;
    }

    /**
     * Convert any Clark notation name to default namespace
     *
     * @param string $name Name
     *
     * @return string
     */
    protected function toDefaultNamespace(string $name): string
    {
        $clark = $this->parseClark($name);
        return '{}' . $clark[1];
    }

    /**
     * Get namespace and local name from a clark string.
     *
     * @param string $clark Clark string
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public static function parseClark(string $clark): array
    {
        if (!str_starts_with($clark, '{') || false === ($p = strpos($clark, '}'))) {
            throw new \InvalidArgumentException("'$clark' is invalid");
        }
        return [substr($clark, 1, $p - 1), substr($clark, $p + 1)];
    }
}
