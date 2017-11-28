<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Memcache extends Cache implements CacheInterface
{
    protected $handler = null;

    public function __construct($options = [])
    {
        if (!extension_loaded('memcache')) {
            throw new Exception('NOT SUPPERT Memcache', 1);
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
        $this->handler = new \Memcache;
        // 支持集群配置
        $hosts = $this->options['host'];
        $ports = $this->options['port'];
        foreach ((array) $hosts as $i => $host) {
            $port = $ports[$i] ?? $ports[0] ?? '11211';
            $this->handler->addServer($host, $port, $this->options['persistent']);
        }
    }

    public function get($key, $default = null)
    {
        return $this->handler->get($this->options['prefix'] . $key);
    }

    public function set($key, $value, $ttl = null)
    {
        if (is_null($ttl)) {
            $ttl = $this->options['expire'];
        }
        return $this->handler->set($this->options['prefix'] . $key, $value, MEMCACHE_COMPRESSED, $ttl) ? true : false;
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
        //
    }

    public function setMultiple($values, $ttl = null)
    {
        //
    }

    public function deleteMultiple($keys)
    {
        //
    }

    public function has($key)
    {
        //
    }
}
