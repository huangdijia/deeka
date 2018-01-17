<?php
namespace deeka\traits;

trait SingletonInstance
{
    public static function getInstance()
    {
        static $instance = null;
        if (is_null($instance)) {
            $class    = __CLASS__;
            $instance = new $class;
        }
        return $instance;
    }
}
