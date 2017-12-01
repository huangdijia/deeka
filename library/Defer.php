<?php
namespace deeka;

class Defer
{
    private static $actions  = [];
    private static $executed = false;

    public static function register(\Closure $action)
    {
        if (!Config::get('defer.on', false)) {
            return;
        }
        self::$actions[] = $action;
    }

    public static function exec()
    {
        if (empty(self::$actions)) {
            return;
        }
        if (self::$executed) {
            return;
        }
        self::$actions = array_reverse(self::$actions);
        foreach (self::$actions as $action) {
            call_user_func_array($action, []);
        }
        self::$executed = true;
    }
}
