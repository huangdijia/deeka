<?php
namespace deeka\queue\driver;

use deeka\Queue;
use deeka\Str;

class File extends Queue
{
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function lpop(string $name = '', int $size = 1)
    {
        // check size
        if ($size <= 0) {
            return false;
        }
        // array_pop
        $items = $this->get($name);
        if ($size == 1) {
            $item   = array_shift($items);
            $return = unserialize($item);
        } else {
            $return = [];
            while ($size > 0) {
                $item     = array_shift($items);
                $return[] = unserialize($item);
                $size--;
            }
        }
        $this->put($name, $items);
        return $return;
    }

    public function lpush(string $name = '', $item = '')
    {
        // array_unshift
        $items = $this->get($name);
        array_unshift($items, serialize($item));
        return $this->put($name, $items);
    }

    public function rpop(string $name = '', int $size = 1)
    {
        // check size
        if ($size <= 0) {
            return false;
        }
        // array_pop
        $items = $this->get($name);
        if ($size == 1) {
            $item   = array_pop($items);
            $return = unserialize($item);
        } else {
            $return = [];
            while ($size > 0) {
                $item     = array_pop($items);
                $return[] = unserialize($item);
                $size--;
            }
        }
        $this->put($name, $items);
        return $return;
    }

    public function rpush(string $name = '', $item = '')
    {
        // array_push
        $items = $this->get($name);
        array_push($items, serialize($item));
        return $this->put($name, $items);
    }

    public function length(string $name = '')
    {
        $items = $this->get($name);
        return count($items);
    }

    public function clear(string $name = '')
    {
        return $this->rm($name);
    }

    private function filename(string $name = '')
    {
        return rtrim($this->options['path'], '/') . '/' . $this->options['prefix'] . Str::parseName($name) . '.php';
    }

    private function put(string $name = '', $vars = '')
    {
        if (empty($vars)) {
            return $this->rm($name);
        }
        return file_put_contents($this->filename($name), '<?php return ' . var_export($vars, 1) . ';');
    }

    private function get(string $name = '')
    {
        $filename = $this->filename($name);
        if (is_file($filename)) {
            $items = include $filename;
            if (!is_array($items)) {
                return [];
            }
            return $items;
        }
        return [];
    }

    private function rm($name)
    {
        $filename = $this->filename($name);
        if (is_file($filename)) {
            return unlink($filename);
        }
        return false;
    }
}
