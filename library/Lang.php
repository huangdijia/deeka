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
        return self::$range = $_GET[Config::get('lang.detect_var', self::$detectVar)] 
            ?? $_COOKIE[Config::get('lang.cookie_var', self::$cookieVar)] 
            ?? self::acceptLang() 
            ?? self::$range;
    }

    public static function allowList(array $list = null)
    {
        if (is_null($list)) {
            return self::$allowList;
        }
        return self::$allowList = $list;
    }

    private static function acceptLang()
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
        return self::$range = strtolower($range);
    }

    public static function all()
    {
        return self::$lang;
    }

    public static function set($key = '', string $value = '', string $range = null): bool
    {
        $range = self::range($range);
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
        $range = self::range($range);
        $key   = strtolower($key);
        if (isset(self::$lang[$range][$key])) {
            $args = array_slice(func_get_args(), 2);
            if (!empty($args)) {
                return vsprintf(self::$lang[$range][$key], $args);
            } else {
                return self::$lang[$range][$key];
            }
        }
        Log::record("lang '{$key}' is undefined in {$range}.", Log::NOTICE);
        return $key;
    }

    public static function has(string $key = '', string $range = null): bool
    {
        $range = self::range($range);
        $key   = strtolower($key);
        return isset(self::$lang[$range][$key]) ? true : false;
    }
}
