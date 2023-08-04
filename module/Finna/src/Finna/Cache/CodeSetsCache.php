<?php

/**
 * Finna Code Sets cache.
 *
 * Implements Finna Code Sets CacheInterface to store cached values in VuFind's cache.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @category Finna
 * @package  Cache
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Cache;

use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use NatLibFi\FinnaCodeSets\CacheInterface;
use NatLibFi\FinnaCodeSets\Exception\ValueNotSetException;

/**
 * Finna Code Sets cache.
 *
 * @category Finna
 * @package  Cache
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class CodeSetsCache implements CacheInterface
{
    /**
     * Cache storage.
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * AipaCodeSetsCache constructor.
     *
     * @param Manager $manager Cache manager
     */
    public function __construct(Manager $manager)
    {
        $this->storage = $manager->getCache('codesets');
    }

    /**
     * Does the cache key exist?
     *
     * @param string $key Cache key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        return $this->storage->hasItem(md5($key));
    }

    /**
     * Get value for cache key.
     *
     * @param string $key Cache key
     *
     * @return mixed
     *
     * @throws ValueNotSetException
     * @throws ExceptionInterface
     */
    public function get(string $key): mixed
    {
        if (!$this->exists($key)) {
            throw new ValueNotSetException($key);
        }
        return $this->storage->getItem(md5($key));
    }

    /**
     * Set value for cache key.
     *
     * @param string $key   Cache key
     * @param mixed  $value Value
     *
     * @return void
     *
     * @throws ExceptionInterface
     */
    public function set(string $key, mixed $value): void
    {
        $this->storage->setItem(md5($key), $value);
    }
}
