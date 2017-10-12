<?php
namespace deeka;

use Exception;

class Security
{
    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    private static function hash(): string
    {
        list($usec, $sec) = explode(' ', microtime());
        srand((float) $sec + ((float) $usec * 100000));
        return md5(rand());
    }

    public static function csrf(): string
    {
        if (!Config::get('csrf.on', false)) {
            return '';
        }
        $token = self::hash();
        $name  = Config::get('var.csrf', '__csrf__');
        Cookie::set($name, $token);
        return sprintf('<input type="hidden" name="%s" value="%s" />', $name, $token);
    }

    public static function checkCsrf(): bool
    {
        if (!Config::get('csrf.on', false)) {
            return;
        }
        $name = Config::get('var.csrf', '__csrf__');
        if (Input::post($name) != Cookie::get($name)) {
            // throw new Exception("csrf", 1);
            return false;
        }
        return true;
    }
}
