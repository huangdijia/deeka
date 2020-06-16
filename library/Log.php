<?php
namespace deeka;

use Exception;
use deeka\Config;
use ReflectionClass;
use Psr\Log\LogLevel;
use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;
use Psr\SimpleCache\CacheInterface;

/**
 * @method static void emergency($message = '', array $contex = [])
 * @method static void alert($message = '', array $contex = [])
 * @method static void critical($message = '', array $contex = [])
 * @method static void error($message = '', array $contex = [])
 * @method static void warning($message = '', array $contex = [])
 * @method static void notice($message = '', array $contex = [])
 * @method static void info($message = '', array $contex = [])
 * @method static void debug($message = '', array $contex = [])
 * @method static void sql($message = '', array $contex = [])
 * @method static void log($level, $message = '', array $contex = [])
 * @method static void record($message = '', $level = Log::LOG)
 * @method static void save($dest = '')
 * @method static void write($message = '', $level = Log::LOG, $dest = '')
 * @method static void clear()
 * @package deeka
 */
class Log
{
    use Singleton;
    use SingletonInstance;
    use SingletonCallable;

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
    // 兼容
    const EMERG = 'emergency';
    const ERR   = 'error';

    protected static $levelMapping = [
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
    protected static $methodMapping = [
        'crit'  => 'critical',
        'emerg' => 'emergency',
        'err'   => 'error',
        'warn'  => 'warning',
    ];
    protected static $config   = [];
    protected static $handlers = [];

    /**
     * Get accessor
     * @return CacheInterface 
     * @throws Exception 
     */
    public static function getAccessor()
    {
        return self::connect();
    }

    /**
     * Init
     * @param array $config 
     * @return void 
     */
    public static function init($config = [])
    {
        self::$config = $config;
    }

    /**
     * Connect a driver
     * @param array $config 
     * @return \Psr\SimpleCache\CacheInterface 
     * @throws Exception 
     */
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
     * @return string
     */
    public static function level($level = '')
    {
        if (is_numeric($level)) {
            return self::$levelMapping[$level] ?? self::INFO;
        }

        return strtoupper($level);
    }

    /**
     * Get constants
     * @param bool $fetch_keys 
     * @return array 
     */
    public static function getConstants($fetch_keys = false)
    {
        static $constants = null;

        if (is_null($constants)) {
            $constants = (new ReflectionClass(__CLASS__))->getConstants();
        }

        if ($fetch_keys) {
            return array_keys($constants);
        }

        return $constants;
    }
}
