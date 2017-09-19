<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\cache\ICache;
use deeka\Config;
use Exception;

class Redis extends Cache implements ICache
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

    public function get($name)
    {
        return $this->handler->get($this->options['prefix'] . $name);
    }

    public function set($name, $value, $expire = null)
    {
        return $this->handler->setex($this->options['prefix'] . $name, $expire ?? $this->options['expire'], $value) ? true : false;
    }

    public function rm($name)
    {
        return $this->handler->del($this->options['prefix'] . $name);
    }

    public function clear()
    {
        return $this->handler->flush();
    }
}
