<?php
namespace deeka;

use deeka\Debug;
use deeka\Log;

class Hook
{
    private static $hooks = [];

    public static function import(array $hooks = array())
    {
        foreach ($hooks as $name => $callable) {
            if (is_array($callback)) {
                foreach ($callback as $cb) {
                    self::register($name, $cb);
                }
            } elseif (is_callable($callback)) {
                self::register($name, $callback);
            }
        }
    }

    public static function register(string $name = '', callable $callback = null, bool $priority = false)
    {
        isset(self::$hooks[$name]) || self::$hooks[$name] = [];
        if ($priority) {
            array_unshift(self::$hooks[$name], $callback);
        } else {
            self::$hooks[$name][] = $callback;
        }
    }

    public static function trigger(string $name = '')
    {
        $args  = func_get_args();
        $name  = array_shift($args);
        $hooks = self::$hooks[$name] ?? [];
        foreach ((array) $hooks as $callback) {
            if (!is_callable($callback)) {
                Log::record("[HOOK] error hook {$name}", Log::ERR);
                continue;
            }
            Debug::remark('hook_exec_start');
            call_user_func_array($callback, $args);
            Debug::remark('hook_exec_end');
            $runtime = Debug::getUseTime('hook_exec_start', 'hook_exec_end');
            APP_DEBUG && Log::record("[HOOK] Run {$name} [runtime:{$runtime}]", Log::INFO);
        }
    }
}
