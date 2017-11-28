<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Memcached extends Cache implements CacheInterface
{
    protected $handler = null;

    public function __construct($options = [])
    {
        if (!extension_loaded('memcached')) {
            throw new Exception('NOT SUPPERT Memcached', 1);
        }
        $defaults = [
            'host'       => explode(',', Config::get('memcache.host', '127.0.0.1')),
            'port'       => explode(',', Config::get('memcache.port', '11211')),
            'timeout'    => Config::get('cache.timeout', false),
            'persistent' => Config::get('memcache.persistent', false),
            'prefix'     => Config::get('cache.prefix'),
            'expire'     => Config::get('cache.expire'),
        ];
        $this->options = array_merge($defaults, $options);
        $this->handler = new \Memcached;
        // 支持集群配置
        $hosts = $this->options['host'];
        $ports = $this->options['port'];
        foreach ((array) $hosts as $i => $host) {
            $port = $ports[$i] ?? $ports[0] ?? '11211';
            $this->handler->addServer($host, $port);
        }
    }

    public function get($key, $default = null)
    {
        return $this->handler->get($this->options['prefix'] . $key) ?? $default;
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->handler->set($this->options['prefix'] . $key, $value, $ttl ?? $this->options['expire']) ? true : false;
    }

    public function delete($key)
    {
        return $this->handler->delete($this->options['prefix'] . $key);
    }

    public function clear()
    {
        return $this->handler->flush();
    }

    public function getMultiple($keys, $default = null)
    {
        return $this->handler->getMulti($keys);
    }

    public function setMultiple($values, $ttl = null)
    {
        return $this->handler->setMulti($values, $ttl);
    }

    public function deleteMultiple($keys)
    {
        return $this->handler->deleteMulti($keys);
    }

    public function has($key)
    {
        return false === $this->get($key) ? false : true;
    }
}
