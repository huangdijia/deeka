<?php
namespace deeka;

use deeka\Debug;
use deeka\Log;
use deeka\Reflect;
use deeka\traits\Singleton;
use Exception;

class Hook
{
    use Singleton;

    private static $hooks = [];

    /**
     * Import hooks
     * @param array $hooks 
     * @return void 
     */
    public static function import(array $hooks = [])
    {
        foreach ($hooks as $name => $callback) {
            if (is_array($callback)) {
                foreach ($callback as $cb) {
                    self::register($name, $cb);
                }
            } elseif (is_callable($callback)) {
                self::register($name, $callback);
            }
        }
    }

    /**
     * Register a hook
     * @param string $name 
     * @param callable|null $callback 
     * @param bool $priority 
     * @return void 
     */
    public static function register(string $name = '', callable $callback = null, bool $priority = false)
    {
        self::$hooks[$name] ?? self::$hooks[$name] = [];

        if ($priority) {
            array_unshift(self::$hooks[$name], $callback);
        } else {
            self::$hooks[$name][] = $callback;
        }
    }

    /**
     * Trigger a hook
     * @param string $name 
     * @return void 
     * @throws Exception 
     */
    public static function trigger(string $name = '')
    {
        $args  = func_get_args();
        $name  = array_shift($args);
        $hooks = self::$hooks[$name] ?? [];

        foreach ((array) $hooks as $key => $callback) {
            if (!is_callable($callback)) {
                Log::record("[HOOK] Error hook {$name}#{$key}", Log::ERROR);
                continue;
            }

            Debug::remark('hook_exec_start');
            Reflect::invokeFunction($callback, $args);
            Debug::remark('hook_exec_end');

            $runtime = Debug::getRangeTime('hook_exec_start', 'hook_exec_end');

            defined('APP_DEBUG') && APP_DEBUG && Log::record("[HOOK] Run {$name}#{$key} [runtime:{$runtime}]", Log::INFO);
        }
    }
}
