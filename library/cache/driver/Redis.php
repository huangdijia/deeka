<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Redis extends Cache implements CacheInterface
{
    protected $handler = null;

    public function __construct($options = [])
    {
        if (!extension_loaded('Redis')) {
            throw new Exception('NOT SUPPERT Redis', 1);
        }
        $defaults = [
            'host'       => Config::get('redis.host', '127.0.0.1'),
            'port'       => Config::get('redis.port', '6379'),
            'timeout'    => Config::get('cache.timeout', false),
            'persistent' => Config::get('redis.persistent', false),
            'prefix'     => Config::get('cache.prefix'),
            'expire'     => Config::get('cache.expire'),
        ];
        $this->options = array_merge($defaults, $options);
        $this->handler = new \Redis;
        if ($this->options['persistent']) {
            $this->handler->pconnect($this->options['host'], $this->options['port']);
        } else {
            $this->handler->connect($this->options['host'], $this->options['port']);
        }
    }

    public function get($key, $default = null)
    {
        return $this->handler->get($this->options['prefix'] . $key) ?? $default;
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->handler->setex($this->options['prefix'] . $key, $ttl ?? $this->options['expire'], $value) ? true : false;
    }

    public function delete($key)
    {
        return $this->handler->del($this->options['prefix'] . $key);
    }

    public function clear()
    {
        return $this->handler->flush();
    }

    public function getMultiple($keys, $default = null)
    {
        // return $this->handler->mGet($keys);
        $resp = [];
        foreach ((array) $keys as $key) {
            $resp[$key] = $this->get($key) ?? $default;
        }
        return $resp;
    }

    public function setMultiple($values, $ttl = null)
    {
        // return $this->handler->mset($values);
        foreach ((array) $values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        return $this->handler->delete($keys);
    }

    public function has($key)
    {
        return $this->handler->exists($key) ? true : false;
    }
}
