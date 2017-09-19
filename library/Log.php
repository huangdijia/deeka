<?php
namespace deeka;

use deeka\Config;
use Exception;
use ReflectionClass;

class Log
{
    const EMERG  = 'EMERG';
    const ALERT  = 'ALERT';
    const CRIT   = 'CRIT';
    const ERR    = 'ERR';
    const WARN   = 'WARN';
    const NOTICE = 'NOTICE';
    const INFO   = 'INFO';
    const DEBUG  = 'DEBUG';
    const SQL    = 'SQL';
    const LOG    = 'LOG';

    protected static $map = [
        1     => 'ERR', // E_ERROR
        2     => 'WARN', // E_WARNING
        4     => 'ERR', // E_PARSE
        8     => 'NOTICE', // E_NOTICE
        16    => 'ERR', // E_CORE_ERROR
        32    => 'WARN', // E_CORE_WARNING
        64    => 'ERR', // E_COMPILE_ERROR
        128   => 'WARN', // E_COMPILE_WARNING
        256   => 'ERR', // E_USER_ERROR
        512   => 'WARN', // E_USER_WARNING
        1024  => 'NOTICE', // E_USER_NOTICE
        2048  => 'ERR', // E_STRICT
        4096  => 'ERR', // E_RECOVERABLE_ERROR
        32767 => 'INFO', // E_ALL
    ];
    protected static $config   = [];
    protected static $handlers = [];

    public static function instance()
    {
        return self::connect();
    }

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array([self::connect(), $name], $args);
    }

    public static function init($config = [])
    {
        self::$config = $config;
    }

    public static function connect($config = [])
    {
        // 合拼默认配置
        $config = array_merge(Config::get('log'), self::$config, $config);
        // 解析类型
        $type = $config['type'] ?? 'file';
        if (false === strpos($type, '\\')) {
            $class = '\\deeka\\log\\driver\\' . ucfirst(strtolower($type));
        }
        // 驱动错误
        if (!class_exists($class)) {
            throw new Exception("Log driver '{$class}' is not exists", 1);
        }
        // 解析key
        $key = md5($class . serialize($config));
        // 单例实例化
        if (
            !isset(self::$handlers[$key])
            || !is_object(self::$handlers[$key])
        ) {
            // 实例化驱动类
            self::$handlers[$key] = new $class($config);
        }
        // 返回对象
        return self::$handlers[$key];
    }

    /**
     * @param $level 级别
     */
    public static function level($level = '')
    {
        if (is_numeric($level) && isset(self::$map[$level])) {
            return self::$map[$level];
        }
        return strtoupper($level);
    }

    public static function getConstants()
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }
}
