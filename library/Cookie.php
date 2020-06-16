<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;

/**
 * @method static void init(array $config = [])
 * @method static mixed prefix(string $prefix = null)
 * @method static bool set($name, $value = '', $option = null)
 * @method static mixed get(string $name = '', string $prefix = null)
 * @method static bool del(string $name, string $prefix = null)
 * @method static bool has(string $name, string $prefix = null)
 * @method static mixed all(string $prefix = null)
 * @method static bool clear(string $prefix = null)
 * @package deeka
 */
class Cookie
{
    use Singleton;
    use SingletonInstance;
    use SingletonCallable;

    protected static $config = [
        'prefix' => '',
        'expire' => 0,
        'path'   => '/',
        'domain' => '',
    ];

    /**
     * Init
     * @param array $config 
     * @return void 
     */
    private function init(array $config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Set prefix
     * @param string|null $prefix 
     * @return string|int|void 
     */
    private function prefix(string $prefix = null)
    {
        if (is_null($prefix)) {
            return self::$config['prefix'];
        } else {
            self::$config['prefix'] = $prefix;
        }
    }

    /**
     * Set cookie
     * @param mixed $name 
     * @param string $value 
     * @param mixed|null $option 
     * @return true 
     */
    private function set($name, $value = '', $option = null)
    {
        if (!is_null($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
            $config = array_merge(self::$config, array_change_key_case($option));
        } else {
            $config = self::$config;
        }
        $name = $config['prefix'] . $name;
        if (is_array($value)) {
            $value = 'array:' . json_encode(array_map('urlencode', $value));
        }
        $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
        setcookie($name, $value, $expire, $config['path'], $config['domain']);
        $_COOKIE[$name] = $value;
        return true;
    }

    /**
     * Get one or all
     * @param string $name 
     * @param string|null $prefix 
     * @return mixed 
     */
    private function get(string $name = '', string $prefix = null)
    {
        $prefix = $prefix ?? self::$config['prefix'];

        if ($name == '') {
            return self::all($prefix);
        }

        $name = $prefix . $name;

        if (!isset($_COOKIE[$name])) {
            return null;
        }

        $value = $_COOKIE[$name];

        if (0 === strpos($value, 'array:')) {
            $value = substr($value, 6);
            return array_map('urldecode', json_decode($value, true));
        }

        return $value;
    }

    /**
     * Remove a cookie
     * @param string $name 
     * @param string|null $prefix 
     * @return void 
     */
    private function del(string $name, string $prefix = null)
    {
        $prefix = $prefix ?? self::$config['prefix'];
        $name   = $prefix . $name;
        setcookie($name, '', time() - 3600, self::$config['path'], self::$config['domain']);
        unset($_COOKIE[$name]);
    }

    /**
     * Check
     * @param string $name 
     * @param string|null $prefix 
     * @return bool 
     */
    private function has(string $name, string $prefix = null)
    {
        $prefix = $prefix ?? self::$config['prefix'];
        $name   = $prefix . $name;
        return isset($_COOKIE[$name]);
    }

    /**
     * Get all
     * @param string|null $prefix 
     * @return array 
     */
    private function all(string $prefix = null)
    {
        if ($prefix == '') {
            return $_COOKIE;
        }
        $cookie = [];
        foreach ($_COOKIE as $key => $val) {
            if (0 === strpos($key, $prefix)) {
                $cookie[$key] = $_COOKIE[$key];
            }
        }
        return $cookie;
    }

    /**
     * Clear all cookies
     * @param string|null $prefix 
     * @return true 
     */
    private function clear(string $prefix = null)
    {
        if (empty($_COOKIE)) {
            return true;
        }

        $prefix = $prefix ?? self::$config['prefix'];

        if ($prefix) {
            foreach ($_COOKIE as $key => $val) {
                if (0 === strpos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, self::$config['path'], self::$config['domain']);
                    unset($_COOKIE[$key]);
                }
            }
        } else {
            unset($_COOKIE);
        }

        return true;
    }
}
