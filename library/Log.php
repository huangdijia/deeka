<?php
namespace deeka;

use deeka\Config;
use Exception;
use ReflectionClass;
use Psr\Log\LogLevel;

class Log
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    const SQL       = 'sql';
    const LOG       = 'log';

    protected static $map = [
        E_ERROR             => LogLevel::ERROR,
        E_WARNING           => LogLevel::WARNING,
        E_PARSE             => LogLevel::ERROR,
        E_NOTICE            => LogLevel::NOTICE,
        E_CORE_ERROR        => LogLevel::ERROR,
        E_CORE_WARNING      => LogLevel::WARNING,
        E_COMPILE_ERROR     => LogLevel::ERROR,
        E_COMPILE_WARNING   => LogLevel::WARNING,
        E_USER_ERROR        => LogLevel::ERROR,
        E_USER_WARNING      => LogLevel::WARNING,
        E_USER_NOTICE       => LogLevel::NOTICE,
        E_STRICT            => LogLevel::ERROR,
        E_RECOVERABLE_ERROR => LogLevel::ERROR,
        E_ALL               => LogLevel::INFO,
    ];
    protected static $mapping  = [
        'crit'  => 'critical',
        'emerg' => 'emergency',
        'err'   => 'error',
        'warn'  => 'warning',
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
        // 旧方法兼容
        if (isset(self::$mapping[$name])) {
            $name = self::$mapping[$name];
        }
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
        if (is_numeric($level)) {
            return self::$map[$level] ?? self::ERR;
        }
        return strtoupper($level);
    }

    public static function getConstants()
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }
}
