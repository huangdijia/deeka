<?php
namespace deeka;

class Cookie
{
    protected static $config = [
        'prefix' => '',
        'expire' => 0,
        'path'   => '/',
        'domain' => '',
    ];
    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public function __call($name, $args)
    {
        return call_user_func_array([self::instance(), $name], $args);
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array([self::instance(), $name], $args);
    }

    private function init(array $config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }

    private function prefix($prefix = null)
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

    private function get($name = '', $prefix = '')
    {
        if ($prefix == '') {
            $prefix = self::$config['prefix'];
        }
        if ($name == '') {
            return self::all($prefix);
        }
        $name = $prefix . $name;
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'array:')) {
                $value = substr($value, 6);
                return array_map('urldecode', json_decode($value, true));
            }
            return $value;
        }
        return null;
    }

    private function del($name, $prefix = '')
    {
        if ($prefix == '') {
            $prefix = self::$config['prefix'];
        }
        $name = $prefix . $name;
        setcookie($name, '', time() - 3600, self::$config['path'], self::$config['domain']);
        unset($_COOKIE[$name]);
    }

    private function has($name, $prefix = '')
    {
        if ($prefix == '') {
            $prefix = self::$config['prefix'];
        }
        $name = $prefix . $name;
        return isset($_COOKIE[$name]);
    }

    private function all($prefix = '')
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

    private function clear($prefix = '')
    {
        if (empty($_COOKIE)) {
            return true;
        }
        if ($prefix == '') {
            $prefix = self::$config['prefix'];
        }
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
