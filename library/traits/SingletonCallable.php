<?php
namespace deeka\traits;

trait SingletonCallable
{
    public function __call($name, $args)
    {
        if (property_exists(self::class, 'methodMapping')) {
            $name = self::$methodMapping[$name] ?? $name;
        }

        return call_user_func_array([self::getInstance(), $name], $args);
    }

    public static function __callStatic($name, $args)
    {
        if (property_exists(self::class, 'methodMapping')) {
            $name = self::$methodMapping[$name] ?? $name;
        }

        return call_user_func_array([self::getInstance(), $name], $args);
    }
}
