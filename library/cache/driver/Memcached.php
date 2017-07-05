<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;

class Memcached extends Cache
{
    protected $handler = null;

    public function __construct($options = [])
    {
        if (!extension_loaded('memcached')) {
            throw new \Exception('NOT SUPPERT Memcached', 1);
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
            $port = isset($ports[$i]) ? $ports[$i] : (isset($ports[0]) ? $ports[0] : '11211');
            $this->handler->addServer($host, $port);
        }
    }

    public function get($name)
    {
        return $this->handler->get($this->options['prefix'] . $name);
    }

    public function set($name, $value, $expire = null)
    {
        return $this->handler->set($this->options['prefix'] . $name, $value, $expire ?? $this->options['expire']) ? true : false;
    }

    public function rm($name, $ttl = false)
    {
        if (false === $ttl) {
            return $this->handler->delete($this->options['prefix'] . $name);
        } else {
            return $this->handler->delete($this->options['prefix'] . $name, $ttl);
        }
    }

    public function clear()
    {
        return $this->handler->flush();
    }
}
