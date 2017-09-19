<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\cache\ICache;
use deeka\Config;
use Exception;

class Memcache extends Cache implements ICache
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
            $port = isset($ports[$i]) ? $ports[$i] : (isset($ports[0]) ? $ports[0] : '11211');
            $this->handler->addServer($host, $port, $this->options['persistent']);
        }
    }

    public function get($name)
    {
        return $this->handler->get($this->options['prefix'] . $name);
    }

    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        return $this->handler->set($this->options['prefix'] . $name, $value, MEMCACHE_COMPRESSED, $expire) ? true : false;
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
