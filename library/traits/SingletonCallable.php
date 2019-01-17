<?php
namespace deeka\traits;

trait SingletonCallable
{
    public function __call($name, $args)
    {
        return call_user_func_array([self::getInstance(), $name], $args);
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array([self::getInstance(), $name], $args);
    }
}