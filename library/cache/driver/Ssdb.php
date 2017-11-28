<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;
use deeka\Loader;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Ssdb extends Cache implements CacheInterface
{
    protected $handler = null;

    public function __construct($options = [])
    {
        Loader::addClassMap('SimpleSSDB', CORE_PATH . '/vendor/ssdb/SSDB.php');
        if (!class_exists('SimpleSSDB')) {
            throw new Exception('Not suppert SimpleSSDB', 1);
        }
        $defaults = [
            'host'    => Config::get('ssdb.host', '127.0.0.1'),
            'port'    => Config::get('ssdb.port', '8888'),
            'prefix'  => Config::get('cache.prefix', 'cache_'),
            'expire'  => Config::get('cache.expire', 120),
            'timeout' => Config::get('cache.timeout', false),
        ];
        $this->options = array_merge($defaults, $this->options, $options);
        try {
            if ($this->options['timeout'] > 0) {
                $this->handler = new \SimpleSSDB($this->options['host'], $this->options['port'], $this->options['timeout']);
            } else {
                $this->handler = new \SimpleSSDB($this->options['host'], $this->options['port']);
            }
        } catch (\SSDBException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function get($key)
    {
        $data = $this->handler->get($this->options['prefix'] . $key);
        return unserialize($data) ?? $default;
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->handler->setx($this->options['prefix'] . $key, serialize($value), $ttl ?? $this->options['expire']);
    }

    public function delete($key)
    {
        return $this->handler->del($this->options['prefix'] . $key);
    }

    public function clear()
    {
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        return $this->handler->multi_get($keys);
    }

    public function setMultiple($values, $ttl = null)
    {
        // return $this->handler->multi_set($values);
        foreach ((array) $values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        return $this->handler->multi_del($keys);
    }

    public function has($key)
    {
        return $this->handler->exists($key);
    }
}
