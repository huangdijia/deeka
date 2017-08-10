<?php
namespace deeka;

use deeka\Config;
use deeka\Log;

class Lang
{
    private static $lang      = [];
    private static $range     = 'zh-cn';
    private static $detectVar = 'lang';
    private static $cookieVar = 'lang';
    private static $allowList = [];

    private function __construct()
    {}

    private function __clone()
    {}

    public static function detect()
    {
        $range = $_GET[Config::get('lang.detect_var', self::$detectVar)] 
            ?? $_COOKIE[Config::get('lang.cookie_var', self::$cookieVar)] 
            ?? self::acceptLanguage()
            ?? self::$range;
        if (!in_array($range, Config::get('lang.allow_list', []))){
            $range = Config::get('default.lang', 'zh-cn');
        }
        self::$range = $range;
        return $range;
    }

    private static function acceptLanguage()
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return null;
        preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
        return !empty($matches[1]) ? strtolower($matches[1]) : null;
    }

    public static function range(string $range = null)
    {
        if (is_null($range)) {
            return self::$range;
        }
        self::$range = strtolower($range);
        return true;
    }

    public static function all() : array
    {
        return self::$lang;
    }

    public static function set($key = '', string $value = '', string $range = null): bool
    {
        $range = $range ?? self::$range;
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::set($k, $v, $range);
            }
        } else {
            $key = strtolower($key);
            if (!isset(self::$lang[$range])) {
                self::$lang[$range] = [];
            }
            self::$lang[$range][$key] = $value;
        }
        return true;
    }

    public static function get(string $key = '', string $range = null): string
    {
        $default = $key;
        $range   = $range ?? self::$range;
        $key     = strtolower($key);
        if (isset(self::$lang[$range][$key])) {
            $args = array_slice(func_get_args(), 2);
            if (!empty($args)) {
                return vsprintf(self::$lang[$range][$key], $args);
            } else {
                return self::$lang[$range][$key];
            }
        }
        Log::record("lang '{$default}' is undefined in {$range}.", Log::NOTICE);
        return $default;
    }

    public static function has(string $key = '', string $range = null): bool
    {
        $range = $range ?? self::$range;
        $key   = strtolower($key);
        return isset(self::$lang[$range][$key]) ? true : false;
    }
}
