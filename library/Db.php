<?php
namespace deeka;

use Exception;

class Db
{
    /**
     * @var array 连接配置
     */
    private static $configs = [];
    /**
     * @var array 实例集合
     */
    private static $instances = [];

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    /**
     * 初始化配置参数
     * @param array $configs
     */
    public static function init(array $configs = null)
    {
        if (!is_null($configs)) {
            self::$configs = $configs;
        }
    }

    /**
     * 增加配置
     * @param string $name 配置名
     * @param array $config 配置参数
     * @param bool $replace 是否覆盖已存在配置
     */
    public static function addConfig(string $name = '', array $config = [], bool $replace = false)
    {
        if (isset(self::$configs[$name]) && !$replace) {
            return false;
        }
        self::$configs[$name] = $config;
        return true;
    }

    /**
     * 实例化数据库对象
     * @param $config 连接配置
     * $db = Db::connect('local');
     * $data = $db->select();
     */
    public static function connect($config = '')
    {
        if ('' == $config) { // 使用默认配置
            $config = current(self::$configs);
        } elseif (is_scalar($config)) { // 加载指定配置
            if (!isset(self::$configs[$config])) { // 未找到配置
                throw new Exception("Db config [{$config}] is not found", 1);
            }
            $config = self::$configs[$config];
        }
        if (!is_array($config)) { // 配置类型不正确
            throw new Exception("Db config datatype error", 1);
        }
        $link_id = md5(serialize($config));
        $driver  = $config['type'] ?? 'mysql';
        if (false === strpos($driver, '\\')) { // 兼容未指定命名空间驱动
            $driver = '\\deeka\\db\\driver\\' . ucfirst(strtolower($driver));
        }
        if (!class_exists($driver)) { // 找不到驱动
            throw new Exception("Db driver '{$driver}' is undefined", 1);
        }
        if (!isset(self::$instances[$link_id])) { // 单例机制
            self::$instances[$link_id] = new $driver($config);
        }
        return self::$instances[$link_id];
    }

    /**
     * 快捷调用
     * Db::selectOne($sql);
     */
    public static function __callStatic($name, $args)
    {
        return call_user_func_array([self::connect(), $name], $args);
    }

    /**
     * 获取缓存
     * @param string $sql SQL语句
     * @param $options 缓存参数
     * @return mixed
     */
    public static function getCache(string $sql = '', $options = '', $suffix = '')
    {
        $options = Cache::parse($options);
        $name    = $options['name'] ?? md5($sql) . '@' . $suffix;
        Debug::remark('sql_cache_begin');
        $value   = Cache::connect($options)->get($name);
        Debug::remark('sql_cache_end');
        if (false !== $value) {
            Log::record(
                sprintf(
                    "[SQLCACHE KEY=%s%s] %s [%f sec]",
                    $options['prefix'] ?? '',
                    $name,
                    $sql,
                    Debug::getRangeTime('sql_cache_begin', 'sql_cache_end')
                ),
                Log::INFO
            );
        }
        return $value;
    }

    /**
     * 设置缓存
     * @param string $sql SQL语句
     * @param $result 缓存结果
     * @param $options 缓存配置
     */
    public static function setCache(string $sql = '', $result = '', $options = '', $suffix = '')
    {
        $options = Cache::parse($options);
        $name    = $options['name'] ?? md5($sql) . '@' . $suffix;
        return Cache::connect($options)->set($name, $result);
    }
}
