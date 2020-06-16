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

    /**
     * Create rand hash
     * @return string 
     */
    private static function hash(): string
    {
        list($usec, $sec) = explode(' ', microtime());
        srand((float) $sec + ((float) $usec * 100000));

        return md5(rand());
    }

    /**
     * Create csrf
     * @return string 
     */
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

    /**
     * Check csrf
     * @return bool 
     */
    public static function checkCsrf(): bool
    {
        if (!Config::get('csrf.on', false)) {
            return true;
        }

        $name = Config::get('var.csrf', '__csrf__');

        if (Input::post($name) != Cookie::get($name)) {
            return false;
        }

        return true;
    }
}
