<?php
namespace deeka\db;

use deeka\Debug;
use deeka\Log;

class Cache
{
    private $handler = null;
    private $sql     = '';
    private $suffix  = '';
    private $name    = '';

    public static function instance($sql = '', $suffix = '', $options = [])
    {
        $class = __CLASS__;
        return new $class($sql, $suffix, $options);
    }

    public function __construct($sql = '', $suffix = '', $options = [])
    {
        $options       = \deeka\Cache::parse($options);
        $this->sql     = $sql;
        $this->suffix  = $suffix;
        $this->name    = $options['name'] ?? md5($sql) . '@' . $suffix;
        $this->handler = \deeka\Cache::connect($options);
    }

    /**
     * 获取缓存
     * @param string $sql
     * @param $options
     * @return mixed
     */
    public function get()
    {
        Debug::remark('sql_cache_begin');
        $value = $this->handler->get($this->name);
        Debug::remark('sql_cache_end');
        if (false !== $value) {
            Log::record(
                sprintf(
                    "[SQLCACHE KEY=%s] %s [%f sec ]",
                    $this->name,
                    $this->sql,
                    Debug::getRangeTime('sql_cache_begin', 'sql_cache_end')
                ),
                Log::INFO
            );
        }
        return $value;
    }

    /**
     * 设置缓存
     * @param string $sql
     * @param $result
     * @param $options
     */
    public function set($result = '')
    {
        return $this->handler->set($this->name, $result);
    }
}
