<?php
namespace deeka\cache\driver;

use deeka\Cache;
use deeka\Config;
use Psr\SimpleCache\CacheInterface;

class File extends Cache implements CacheInterface
{
    // 构造函数
    public function __construct($options = [])
    {
        if (is_array($options) && !empty($options)) {
            $this->options = $options;
        }

        $this->options = array_merge(
            [
                'path'   => Config::get('cache.path'),
                'prefix' => Config::get('cache.prefix'),
                'expire' => Config::get('cache.expire'),
                'check'  => Config::get('cache.check'),
            ],
            $this->options
        );

        $this->init();
    }

    private function init()
    {
        // 创建项目缓存目录
        if (!is_dir($this->options['path'])) {
            mkdir($this->options['path']);
        }
    }

    private function filename($key)
    {
        return rtrim($this->options['path'], '/') . '/' . $this->options['prefix'] . md5($key) . '.php';
    }

    public function get($key, $default = null)
    {
        $filename = $this->filename($key);

        if (!is_file($filename)) {
            return false;
        }

        $content = file_get_contents($filename);

        if (false === $content) {
            return false;
        }

        // 有效期
        $expire = (int) substr($content, 8, 12);
        if ($expire > 0 && time() > filemtime($filename) + $expire) {
            unlink($filename);
            return false;
        }

        // 内容校验
        if ($this->options['check']) {
            $check   = substr($content, 20, 32);
            $content = substr($content, 52, -3);
            if ($check != md5($content)) {
                return false;
            }
        } else {
            $content = substr($content, 20, -3);
        }

        // 反序列化
        $content = unserialize($content);

        return $content ?? $default;
    }

    public function set($key, $value, $ttl = null)
    {
        if (is_null($ttl)) {
            $ttl = $this->options['expire'];
        }

        $filename = $this->filename($key);
        $data     = serialize($value);

        // 生成校验码
        if ($this->options['check']) {
            $check = md5($data);
        } else {
            $check = '';
        }

        // 缓存内容
        $content = "<?php\n//" . sprintf('%012d', $ttl) . $check . $data . "\n?>";
        // 保存文件
        $result = file_put_contents($filename, $content);

        if ($result) {
            clearstatcache();
            return true;
        }

        return false;
    }

    public function delete($key)
    {
        $filename = $this->filename($key);

        if (is_file($filename)) {
            return unlink($filename);
        }

        return false;
    }

    public function clear()
    {
        $path   = $this->options['path'];
        $prefix = $this->options['prefix'];
        $files  = scandir($path);

        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && is_dir($path . $file)) {
                    array_map('unlink', glob($path . $file . '/' . $prefix . '*.*'));
                } elseif (is_file($path . $file)) {
                    unlink($path . $file);
                }
            }

            return true;
        }

        return false;
    }

    public function getMultiple($keys, $default = null)
    {
        $retval = [];

        foreach ((array) $keys as $key) {
            $retval[$key] = $this->get($key, $default);
        }

        return $retval;
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
        foreach ((array) $keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key)
    {
        $filename = $this->filename($key);
        return is_file($filename) ? true : false;
    }
}
