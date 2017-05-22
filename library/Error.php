<?php
namespace deeka;

use deeka\exception\ErrorException;
use deeka\Response;

class Error
{
    // protected static $errorTable = [
    //     E_ERROR             => 'Error',
    //     E_WARNING           => 'Warning',
    //     E_PARSE             => 'Parse',
    //     E_NOTICE            => 'Notice',
    //     E_CORE_ERROR        => 'Core Error',
    //     E_CORE_WARNING      => 'Core Warning',
    //     E_COMPILE_ERROR     => 'Compile Error',
    //     E_COMPILE_WARNING   => 'Compile Warning',
    //     E_USER_ERROR        => 'User Error',
    //     E_USER_WARNING      => 'User Warning',
    //     E_USER_NOTICE       => 'User Notice',
    //     E_STRICT            => 'Strict',
    //     E_RECOVERABLE_ERROR => 'Recoverable Error',
    //     E_DEPRECATED        => 'Deprecated',
    //     E_USER_DEPRECATED   => 'User Deprecated',
    //     E_ALL               => 'All',
    // ];
    private static $debug = false;

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, 'exceptionHandler']);
        register_shutdown_function([__CLASS__, 'shutdownHandler']);
    }

    public static function shutdownHandler()
    {
        self::$debug && Log::record(__METHOD__, Log::DEBUG);
        if (
            !is_null($error = error_get_last())
        ) {
            Log::record($error, Log::DEBUG);
            if (self::isFatal($error['type'])) {
                // 将错误信息托管至 deeka\exception\ErrorException
                $e = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);
                self::exceptionHandler($e);
            } else {
                // self::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
            }
        }
        // 写入日志
        Log::save();
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext = [])
    {
        self::$debug && Log::record(__METHOD__, Log::DEBUG);
        $e = new ErrorException($errno, $errstr, $errfile, $errline, $errcontext);
        if (
            (error_reporting() & $errno)
            && self::isFatal($errno)
        ) {
            // 将致命错误信息托管至 deeka\exception\ErrorException
            self::exceptionHandler($e);
        } else {
            // 将警示错误信息托管至 Log::record
            $log = sprintf(
                "%s[%s] in %s on line %s",
                $errstr,
                $errno,
                $errfile,
                $errline
            );
            // 记录日志
            Log::record($log, $errno);
        }
    }

    public static function exceptionHandler($e)
    {
        self::$debug && Log::record(__METHOD__, Log::DEBUG);
        $log = sprintf(
            "%s in %s on line %s\n\nTrack:\n%s\n",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        // 记录日志
        Log::record($log, is_callable([$e, 'getSeverity']) ? $e->getSeverity() : Log::ERR);
        Log::save();
        @ob_end_clean();
        if (APP_DEBUG) {
            if (!Request::isCli()) {
                $log = nl2br($log);
            }
            Response::instance()->halt(Config::get('error.code', 500), $log);
        } else {
            Response::instance()->halt(Config::get('error.code', 500), $e->getMessage());
        }
    }

    protected static function isFatal($type = 0)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }
}
