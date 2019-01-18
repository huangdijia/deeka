<?php
namespace deeka\traits;

trait SingletonInstance
{
    public static function getInstance()
    {
        static $instance = null;

        if (!is_null($instance)) {
            return $instance;
        }

        if (method_exists(self::class, 'getAccessor')) {
            $instance = self::getAccessor();
        } else {
            $instance = new self;
        }

        return $instance;
    }
}
