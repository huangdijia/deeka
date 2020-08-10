<?php
namespace deeka;

use deeka\Config;
use deeka\log\Manager;
use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;
use Exception;
use Psr\Log\LogLevel;
use ReflectionClass;

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
 * @method static void log($message = '', array $contex = [])
 * @method static void record($message = '', $level = Log::LOG, array $contex = [])
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
    protected static $config  = [];
    protected static $manager = null;

    /**
     * Get accessor
     * @return Manager
     * @throws Exception
     */
    public static function getAccessor()
    {
        return self::connect(self::$config);
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
     * @return Manager
     * @throws Exception
     */
    public static function connect($config = [])
    {
        $config = array_merge(Config::get('log'), self::$config, $config);

        if (is_null(self::$manager)) {
            self::$manager = new Manager($config);
        }

        // 返回对象
        return self::$manager;
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
