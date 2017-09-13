<?php
namespace deeka;

use deeka\input\Handler;

/*
 * Input::setGlobalFilter('addslashes, htmlspecialchars');
 * Input::$fun([key], [default], [filter])
 */
class Input
{
    protected static $handler;

    public static function instance()
    {
        if (is_null(self::$handler)) {
            self::$handler = new static;
        }
        return self::$handler;
    }

    protected static function handler()
    {
        static $handler = null;
        if (is_null($handler)) {
            $handler = new Handler;
        }
        return $handler;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([self::handler(), $method], $args);
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::handler(), $method], $args);
    }
}
