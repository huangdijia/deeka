<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;

/**
 * @method static string detect()
 * @method static string|null acceptLanguage()
 * @method static string range(string $range = null)
 * @method static array all()
 * @method static bool set($key = '', string $value = '', string $range = null)
 * @method static string get(string $key = '', string $range = null)
 * @method static bool has(string $key = '', string $range = null)
 * @package deeka
 */
class Lang
{
    use Singleton;
    use SingletonCallable;
    use SingletonInstance;

    private static $lang      = [];
    private static $range     = 'zh-cn';
    private static $detectVar = 'lang';
    private static $cookieVar = 'lang';
    private static $allowList = [];

    /**
     * detect
     * @return mixed 
     */
    private function detect()
    {
        $range = $_GET[Config::get('lang.detect_var', self::$detectVar)] ?? $_COOKIE[Config::get('lang.cookie_var', self::$cookieVar)] ?? self::acceptLanguage() ?? self::$range;

        if (!in_array($range, Config::get('lang.accept'))) {
            $range = Config::get('default.lang', 'zh-cn');
        }

        if (
            !isset($_COOKIE[Config::get('lang.cookie_var', self::$cookieVar)])
            || $range != $_COOKIE[Config::get('lang.cookie_var', self::$cookieVar)]
        ) {
            setcookie(Config::get('lang.cookie_var', self::$cookieVar), $range, time() + 30 * 24 * 3600, '/');
        }

        self::$range = $range;

        return $range;
    }

    /**
     * Get appect language from header
     * @return string|null 
     */
    private static function acceptLanguage()
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);

        return !empty($matches[1]) ? strtolower($matches[1]) : null;
    }

    /**
     * Range
     * @param string|null $range 
     * @return string|true 
     */
    private function range(string $range = null)
    {
        if (is_null($range)) {
            return self::$range;
        }

        self::$range = strtolower($range);

        return true;
    }

    /**
     * Get all
     * @return array 
     */
    private function all(): array
    {
        return self::$lang;
    }

    /**
     * Set
     * @param string $key 
     * @param string $value 
     * @param string|null $range 
     * @return bool 
     */
    private function set($key = '', string $value = '', string $range = null): bool
    {
        $range = $range ?? self::$range;
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::set($k, $v, $range);
            }
            return true;
        }
        $key = strtolower($key);
        if (!isset(self::$lang[$range])) {
            self::$lang[$range] = [];
        }
        self::$lang[$range][$key] = $value;
        return true;
    }

    /**
     * Get
     * @param string $key 
     * @param string|null $range 
     * @return string 
     */
    private function get(string $key = '', string $range = null): string
    {
        $default = $key;
        $range   = $range ?? self::$range;
        $key     = strtolower($key);
        if (isset(self::$lang[$range][$key])) {
            $args = array_slice(func_get_args(), 2);
            if (!empty($args)) {
                return vsprintf(self::$lang[$range][$key], $args);
            }
            return self::$lang[$range][$key];
        }
        Log::record("lang '{$default}' is undefined in {$range}.", Log::NOTICE);
        return $default;
    }

    /**
     * Is has
     * @param string $key 
     * @param string|null $range 
     * @return bool 
     */
    private function has(string $key = '', string $range = null): bool
    {
        $range = $range ?? self::$range;
        $key   = strtolower($key);
        return isset(self::$lang[$range][$key]) ? true : false;
    }
}
