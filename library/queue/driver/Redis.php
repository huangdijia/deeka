<?php
namespace deeka\queue\driver;

use deeka\Config;
use deeka\Queue;
use Exception;
use RedisException;

class Redis extends Queue
{
    private $handler = null;

    public function __construct(array $options = [])
    {
        $this->options = array_merge(
            $this->options,
            [
                'host' => Config::get('redis.host'),
                'port' => Config::get('redis.port'),
            ],
            $options
        );
        if (!class_exists('Redis')) {
            throw new Exception('Not suppert Redis', 1);
        }
        try {
            $this->handler = new \Redis();
            $this->handler->connect($this->options['host'], $this->options['port']);
        } catch (RedisException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function lpop(string $name = '', int $size = 1)
    {
        if ($size <= 0) {
            return false;
        }
        if ($size == 1) {
            return unserialize($this->handler->lpop($this->options['prefix'] . $name));
        }
        $items = [];
        while ($size > 0) {
            $items[] = $this->handler->lpop($this->options['prefix'] . $name);
            $size--;
        }
        if (empty($items)) {
            return false;
        }
        return array_map('unserialize', $items);
    }

    public function lpush(string $name = '', $item = '')
    {
        return $this->handler->lpush($this->options['prefix'] . $name, serialize($item));
    }

    public function rpop(string $name = '', int $size = 1)
    {
        if ($size <= 0) {
            return false;
        }
        if ($size == 1) {
            return unserialize($this->handler->rpop($this->options['prefix'] . $name));
        }
        $items = [];
        while ($size > 0) {
            $items[] = $this->handler->rpop($this->options['prefix'] . $name, $size);
            $size--;
        }
        if (empty($items)) {
            return false;
        }
        return array_map('unserialize', $items);
    }

    public function rpush(string $name = '', $item = '')
    {
        return $this->handler->rpush($this->options['prefix'] . $name, serialize($item));
    }

    public function length(string $name = '')
    {
        return $this->handler->llen($this->options['prefix'] . $name);
    }

    public function clear(string $name = '')
    {
        return $this->handler->lremove($this->options['prefix'] . $name);
    }
}
