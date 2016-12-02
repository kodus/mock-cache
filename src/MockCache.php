<?php

namespace Kodus\Cache;

use DateInterval;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

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

    public function get($key, $default = null)
    {
        return isset($this->cache_expiration[$key]) && ($this->time < $this->cache_expiration[$key])
            ? unserialize($this->cache[$key])
            : $default;
    }

    public function set($key, $value, $ttl = null)
    {
        if (preg_match(self::PSR16_RESERVED, $key, $match) === 1) {
            throw new InvalidArgumentException("invalid character in key: {$match[0]}");
        }

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

    public function delete($key)
    {
        unset($this->cache[$key]);
        unset($this->cache_expiration[$key]);
    }

    public function clear()
    {
        $this->cache = [];
        $this->cache_expiration = [];
    }

    public function getMultiple($keys)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function setMultiple($items, $ttl = null)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    public function has($key)
    {
        return $this->get($key, $this) !== $this;
    }
}
