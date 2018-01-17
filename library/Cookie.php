<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;

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

    private function init(array $config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }

    private function prefix(string $prefix = null)
    {
        if (is_null($prefix)) {
            return self::$config['prefix'];
        } else {
            self::$config['prefix'] = $prefix;
        }
    }

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

    private function del(string $name, string $prefix = null)
    {
        $prefix = $prefix ?? self::$config['prefix'];
        $name   = $prefix . $name;
        setcookie($name, '', time() - 3600, self::$config['path'], self::$config['domain']);
        unset($_COOKIE[$name]);
    }

    private function has(string $name, string $prefix = null)
    {
        $prefix = $prefix ?? self::$config['prefix'];
        $name   = $prefix . $name;
        return isset($_COOKIE[$name]);
    }

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
