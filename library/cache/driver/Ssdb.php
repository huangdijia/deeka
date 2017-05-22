<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;
use deeka\Loader;

class Ssdb extends Cache
{
    protected $handler = null;

    public function __construct($options = [])
    {
        Loader::addClassMap('SimpleSSDB', CORE_PATH . '/vendor/ssdb/SSDB.php');
        if (!class_exists('SimpleSSDB')) {
            throw new \Exception('Not suppert SimpleSSDB', 1);
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
        }
    }

    public function set($name, $value, $expire = null)
    {
        return $this->handler->setx($this->options['prefix'] . $name, serialize($value), !is_null($expire) ? $expire : $this->options['expire']);
    }

    public function get($name)
    {
        $data = $this->handler->get($this->options['prefix'] . $name);
        return unserialize($data);
    }

    public function rm($name)
    {
        return $this->handler->del($this->options['prefix'] . $name);
    }

    public function clear()
    {
        return true;
    }
}
