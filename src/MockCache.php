<?php

namespace Kodus\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * WARNING! Don't use in production, only use for automated testing!
 *
 * This is a mock PSR-16 simple cache implementation - it's internal state survives only for the
 * lifetime of the object itself.
 */
class MockCache implements CacheInterface
{
    /**
     * @var int
     */
    const DEFAULT_TTL = 86400;

    /**
     * @var string control characters for keys, reserved by PSR-16
     */
    const PSR16_RESERVED = '/\{|\}|\(|\)|\/|\\\\|\@|\:/u';

    /**
     * @var array map where cache key => serialized value
     */
    protected $cache = [];

    /**
     * @var int[] map where cache key => expiration timestamp
     */
    protected $cache_expiration = [];

    /**
     * @var int current frozen timestamp
     */
    protected $time;

    /**
     * @var int
     */
    protected $default_ttl;

    /**
     * @param int $default_ttl default time-to-live (in seconds)
     */
    public function __construct($default_ttl = self::DEFAULT_TTL)
    {
        $this->default_ttl = $default_ttl;
        $this->time = 0;
    }

    /**
     * @param int $seconds
     */
    public function skipTime($seconds)
    {
        $this->time += $seconds;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        return isset($this->cache_expiration[$key]) && ($this->time < $this->cache_expiration[$key])
            ? unserialize($this->cache[$key])
            : $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->validateKey($key);

        if (is_int($ttl)) {
            $expires_at = $this->time + $ttl;
        } elseif ($ttl instanceof DateInterval) {
            $expires_at = date_create_from_format("U", $this->time)->add($ttl)->getTimestamp();
        } elseif ($ttl === null) {
            $expires_at = $this->time + $this->default_ttl;
        } else {
            throw new InvalidArgumentException("invalid TTL: " . print_r($ttl, true));
        }

        $this->cache[$key] = serialize($value);
        $this->cache_expiration[$key] = $expires_at;

        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        $success = true;

        if (! isset($this->cache[$key]) || ! isset($this->cache_expiration[$key])) {
            $success = false;
        }

        unset($this->cache[$key]);
        unset($this->cache_expiration[$key]);

        return $success;
    }

    public function clear(): bool
    {
        $this->cache = [];
        $this->cache_expiration = [];
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if (! is_array($keys) && ! $keys instanceof Traversable) {
            throw new InvalidArgumentException("keys must be either of type array or Traversable");
        }

        $values = [];

        foreach ($keys as $key) {
            $this->validateKey($key);
            $values[$key] = $this->get($key) ?: $default;
        }

        return $values;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        if (! is_array($values) && ! $values instanceof Traversable) {
            throw new InvalidArgumentException("keys must be either of type array or Traversable");
        }

        foreach ($values as $key => $value) {
            $this->validateKey($key);
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        if (! is_array($keys) && ! $keys instanceof Traversable) {
            throw new InvalidArgumentException("keys must be either of type array or Traversable");
        }

        foreach ($keys as $key) {
            $this->validateKey($key);
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    protected function validateKey($key)
    {
        if (preg_match(self::PSR16_RESERVED, $key, $match) === 1) {
            throw new InvalidArgumentException("invalid character in key: {$match[0]}");
        }
    }
}
