<?php
namespace deeka;

use deeka\exception\ErrorException;
use deeka\Response;
use deeka\traits\Singleton;

class Error
{
    use Singleton;

    private static $debug = false;

    /**
     * Register
     * @return void 
     */
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, 'exceptionHandler']);
        register_shutdown_function([__CLASS__, 'shutdownHandler']);
    }

    /**
     * Shutdown handler
     * @return void 
     * @throws Exception 
     */
    public static function shutdownHandler()
    {
        self::$debug && Log::record(__METHOD__, Log::DEBUG);
        // exception
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
        !Defer::isDefered() && Log::save();
    }

    /**
     * Error handler
     * @param mixed $errno 
     * @param mixed $errstr 
     * @param mixed $errfile 
     * @param mixed $errline 
     * @param array $errcontext 
     * @return void 
     * @throws Exception 
     */
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

    /**
     * Exception handler
     * @param mixed $e 
     * @return void 
     * @throws Exception 
     */
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
        Log::record($log, is_callable([$e, 'getSeverity']) ? $e->getSeverity() : Log::ERROR);
        !Defer::isDefered() && Log::save();
        ob_get_length() && ob_end_clean();
        if (APP_DEBUG) {
            if (!Request::isCli()) {
                $log = nl2br($log);
            }
            Response::instance()->halt(Config::get('error.code', 500), $log);
        } else {
            Response::instance()->halt(Config::get('error.code', 500), $e->getMessage());
        }
    }

    /**
     * Is fatal error
     * @param int $type 
     * @return bool 
     */
    protected static function isFatal($type = 0)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }
}
