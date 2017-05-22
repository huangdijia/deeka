<?php
namespace deeka\queue\driver;

use deeka\Queue;
use deeka\Config;
use deeka\Loader;

class Ssdb extends Queue
{
    private $handler = null;

    public function __construct(array $options = [])
    {
        $this->options = array_merge(
            $this->options,
            [
                'host' => Config::get('ssdb.host'),
                'port' => Config::get('ssdb.port'),
            ],
            $options
        );
        Loader::addClassMap('SimpleSSDB', CORE_PATH . '/vendor/ssdb/SSDB.php');
        if (!class_exists('SimpleSSDB')) {
            throw new \Exception('Not suppert SimpleSSDB', 1);
        }
        try {
            $this->handler = new \SimpleSSDB($this->options['host'], $this->options['port']);
        } catch (\SSDBException $e) {
            throw $e;
        }
    }

    public function lpop(string $name = '', int $size = 1)
    {
        if ($size <= 0) {
            return false;
        }
        if ($size == 1) {
            return unserialize($this->handler->qpop_front($this->options['prefix'] . $name));
        }
        $items = $this->handler->qpop_front($this->options['prefix'] . $name, $size);
        if (false === $items) {
            return false;
        }
        return array_map('unserialize', $items);
    }

    public function lpush(string $name = '', $item = '')
    {
        return $this->handler->qpush_front($this->options['prefix'] . $name, serialize($item));
    }

    public function rpop(string $name = '', int $size = 1)
    {
        if ($size <= 0) {
            return false;
        }
        if ($size == 1) {
            return unserialize($this->handler->qpop_back($this->options['prefix'] . $name));
        }
        $items = $this->handler->qpop_back($this->options['prefix'] . $name, $size);
        if (false === $items) {
            return false;
        }
        return array_map('unserialize', $items);
    }

    public function rpush(string $name = '', $item = '')
    {
        return $this->handler->qpush_back($this->options['prefix'] . $name, serialize($item));
    }

    public function length(string $name = '')
    {
        return $this->handler->qsize($this->options['prefix'] . $name);
    }

    public function clear(string $name = '')
    {
        return $this->handler->clear($this->options['prefix'] . $name);
    }
}
