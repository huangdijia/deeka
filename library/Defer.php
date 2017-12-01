<?php
namespace deeka;

class Defer
{
    private static $actions = [];

    public static function register(\Closure $action)
    {
        self::$actions[] = $action;
    }

    public static function exec()
    {
        if (empty(self::$actions)) {
            return;
        }
        self::$actions = array_reverse(self::$actions);
        foreach (self::$actions as $action) {
            call_user_func_array($action, []);
        }
    }
}
