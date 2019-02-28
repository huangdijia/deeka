<?php
namespace deeka\traits;

trait SingletonCallable
{
    protected static $methodMapping = [];

    public function __call($name, $args)
    {
        // $name = self::$methodMapping[$name] ?? $name;

        return call_user_func_array([self::getInstance(), $name], $args);
    }

    public static function __callStatic($name, $args)
    {
        // $name = self::$methodMapping[$name] ?? $name;

        return call_user_func_array([self::getInstance(), $name], $args);
    }
}
