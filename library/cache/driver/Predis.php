<?php

namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;
use Predis\Client;
use Psr\SimpleCache\CacheInterface;

class Predis extends Cache implements CacheInterface
{
    protected $handler = null;

    public function __construct($options = [])
    {
        // [
        //     'tcp://192.168.1.198:7000',
        //     'tcp://192.168.1.198:7001',
        //     'tcp://192.168.1.198:7002',
        //     'tcp://192.168.1.199:7003',
        //     'tcp://192.168.1.199:7004',
        //     'tcp://192.168.1.199:7005',
        // ];

        // 集群
        if (Config::get('redis.cluster', false)) {
            $this->handler = new Client(
                Config::get(Config::get('cache.connection', 'redis.clusters.default'), []),
                [
                    'cluster' => Config::get('redis.options.cluster', 'redis'),
                ]
            );
        } else {
            $this->handler = new Client([
                'scheme' => 'tcp',
                'host'   => Config::get('redis.host', '127.0.0.1'),
                'port'   => Config::get('redis.port', '6379'),
            ]);
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
        $resp = [];
        foreach ((array) $keys as $key) {
            $resp[$key] = $this->get($key) ?? $default;
        }
        return $resp;
    }

    public function setMultiple($values, $ttl = null)
    {
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
