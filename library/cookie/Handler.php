<?php
namespace deeka\cookie;

class Handler
{
    protected static $config = [
        'prefix' => '',
        'expire' => 0,
        'path'   => '/',
        'domain' => '',
    ];

    public function init(array $config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }

    public function prefix($prefix = null)
    {
        if (is_null($prefix)) {
            return self::$config['prefix'];
        } else {
            self::$config['prefix'] = $prefix;
        }
    }

    public function set($name, $value = '', $option = null)
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

    public function get($name = '', $prefix = '')
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

    public function del($name, $prefix = '')
    {
        if ($prefix == '') {
            $prefix = self::$config['prefix'];
        }
        $name = $prefix . $name;
        setcookie($name, '', time() - 3600, self::$config['path'], self::$config['domain']);
        unset($_COOKIE[$name]);
    }

    public function has($name, $prefix = '')
    {
        if ($prefix == '') {
            $prefix = self::$config['prefix'];
        }
        $name = $prefix . $name;
        return isset($_COOKIE[$name]);
    }

    public function all($prefix = '')
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

    public function clear($prefix = '')
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
