<?php
namespace deeka;

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

    private static function hash()
    {
        list($usec, $sec) = explode(' ', microtime());
        srand((float) $sec + ((float) $usec * 100000));
        return md5(rand());
    }

    public static function csrf()
    {
        if (!Config::get('csrf.on', false)) {
            return '';
        }
        $token    = self::hash();
        $var_name = Config::get('var.csrf', '__csrf__');
        Cookie::set($var_name, $token);
        return sprintf('<input type="hidden" name="%s" value="%s" />', $var_name, $token);
    }

    public static function checkCsrf()
    {
        if (!Config::get('csrf.on', false)) {
            return;
        }
        $var_name = Config::get('var.csrf', '__csrf__');
        if (Input::post($var_name) != Cookie::get($var_name)) {
            throw new \Exception("csrf", 1);
        }
    }
}
