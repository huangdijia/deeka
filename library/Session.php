<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\traits\SingletonInstance;
use Exception;

/**
 * @method static void init(array $config = [])
 * @method static mixed get(string $name = '', string $namespace = null)
 * @method static bool set(string $name, $value = '', string $namespace = null)
 * @method static bool del(string $name, string $namespace = null)
 * @method static bool has(string $name, string $namespace = null)
 * @method static mixed all(string $namespace = null)
 * @method static bool clear(string $namespace = null)
 * @method static bool operate(string $name = '')
 * @package deeka
 */
class Session
{
    use Singleton;
    use SingletonInstance;

    protected static $config = [
        'type'           => '',
        'auto_start'     => 0,
        'var_session_id' => '',
        'id'             => '',
        'name'           => '',
        'path'           => '',
        'domain'         => '',
        'namespace'      => '',
        'expire'         => '',
        'use_trans_sid'  => 0,
        'use_cookies'    => 1,
        'cache_limiter'  => '',
        'cache_expire'   => '',
    ];

    public function __call($name, $args)
    {
        if (in_array($name, ['start', 'pause', 'destroy', 'regenerate'])) {
            return call_user_func_array([self::getInstance(), 'operate'], [$name]);
        }

        return call_user_func_array([self::getInstance(), $name], $args);
    }

    public static function __callStatic($name, $args)
    {
        if (in_array($name, ['start', 'pause', 'destroy', 'regenerate'])) {
            return call_user_func_array([self::getInstance(), 'operate'], [$name]);
        }

        return call_user_func_array([self::getInstance(), $name], $args);
    }

    /**
     * Init
     * @param array $config 
     * @return void 
     * @throws Exception 
     */
    private function init(array $config = [])
    {
        // 缓存配置
        self::$config = $config = array_merge(self::$config, $config);

        // session初始化 在session_start 之前调用
        if (!empty($config['var_session_id']) && isset($_REQUEST[$config['var_session_id']])) {
            session_id(Input::request($config['var_session_id']));
        } elseif (!empty($config['id'])) {
            session_id($config['id']);
        } elseif (Input::request($config['var_session_id']) == "undefined") {
            session_id();
        }

        ini_set('session.auto_start', 0);

        if (!empty($config['name'])) {
            session_name($config['name']);
        }

        if (!empty($config['path'])) {
            session_save_path($config['path']);
        }

        if (!empty($config['domain'])) {
            ini_set('session.cookie_domain', $config['domain']);
        }

        if (!empty($config['expire'])) {
            ini_set('session.gc_maxlifetime', $config['expire']);
        }

        if (isset($config['use_trans_sid'])) {
            ini_set('session.use_trans_sid', $config['use_trans_sid'] ? 1 : 0);
        }

        if (isset($config['use_cookies'])) {
            ini_set('session.use_cookies', $config['use_cookies'] ? 1 : 0);
        }

        if (!empty($config['cache_limiter'])) {
            session_cache_limiter($config['cache_limiter']);
        }

        if (!empty($config['cache_expire'])) {
            session_cache_expire($config['cache_expire']);
        }

        if ($config['type']) {
            // 读取session驱动
            $class = $config['type'];
            if (false === strpos($class, '\\')) {
                $class = '\\deeka\\session\\driver\\' . ucfirst(strtolower($class));
            }
            if (!class_exists($class)) {
                throw new Exception("Bad session driver {$class}", 1);
            }
            $handler = new $class();
            session_set_save_handler(
                [ & $handler, "open"],
                [ & $handler, "close"],
                [ & $handler, "read"],
                [ & $handler, "write"],
                [ & $handler, "destroy"],
                [ & $handler, "gc"]
            );
        }

        // 启动session
        if ($config['auto_start']) {
            session_start();
        }
    }

    /**
     * Get
     * @param string $name 
     * @param string|null $namespace 
     * @return mixed 
     */
    private function get(string $name = '', string $namespace = null)
    {
        $namespace = $namespace ?? self::$config['namespace'];

        if ($namespace) {
            if ('' === $name) {
                return $_SESSION[$namespace] ?? null;
            } else {
                return $_SESSION[$namespace][$name] ?? null;
            }
        } else {
            if ('' === $name) {
                return $_SESSION;
            } else {
                return $_SESSION[$name] ?? null;
            }
        }
    }

    /**
     * Set
     * @param string $name 
     * @param string $value 
     * @param string|null $namespace 
     * @return bool 
     */
    private function set(string $name, $value = '', string $namespace = null): bool
    {
        $namespace = $namespace ?? self::$config['namespace'];

        if ($namespace) {
            if (!isset($_SESSION[$namespace])) {
                $_SESSION[$namespace] = [];
            }
            $_SESSION[$namespace][$name] = $value;
        } else {
            $_SESSION[$name] = $value;
        }

        return true;
    }

    /**
     * Delete
     * @param string $name 
     * @param string|null $namespace 
     * @return bool 
     */
    private function del(string $name, string $namespace = null): bool
    {
        $namespace = $namespace ?? self::$config['namespace'];

        if ($namespace) {
            if (isset($_SESSION[$namespace][$name])) {
                unset($_SESSION[$namespace][$name]);
            }
            return true;
        }

        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }

        return true;
    }

    /**
     * Check
     * @param string $name 
     * @param string|null $namespace 
     * @return bool 
     */
    private function has(string $name, string $namespace = null): bool
    {
        $namespace = $namespace ?? self::$config['namespace'];

        if ($namespace) {
            return isset($_SESSION[$namespace][$name]);
        }

        return isset($_SESSION[$name]);
    }

    /**
     * Get all
     * @param string|null $namespace 
     * @return mixed 
     */
    private function all(string $namespace = null)
    {
        $namespace = $namespace ?? self::$config['namespace'];

        if ($namespace) {
            return $_SESSION[$namespace] ?? null;
        }

        return $_SESSION;
    }

    /**
     * Clear all
     * @param string|null $namespace 
     * @return bool 
     */
    private function clear(string $namespace = null): bool
    {
        $namespace = $namespace ?? self::$config['namespace'];

        if ($namespace) {
            if (isset($_SESSION[$namespace])) {
                unset($_SESSION[$namespace]);
            }
            return true;
        }

        $_SESSION = [];

        return true;
    }

    /**
     * Operate
     * @param string $name 
     * @return bool 
     */
    private function operate(string $name = ''): bool
    {
        switch ($name) {
            case 'start':
                session_start();
                break;
            case 'pause':
                session_write_close();
                break;
            case 'destroy':
                $_SESSION = [];
                session_unset();
                session_destroy();
                break;
            case 'regenerate':
                session_regenerate_id();
                break;
        }

        return true;
    }

    /**
     * Encode
     * @param mixed $array 
     * @param bool $safe 
     * @param string $method 
     * @return string 
     * @throws Exception 
     */
    public static function encode($array, $safe = true, $method = '')
    {
        $method = empty($method) ? ini_get("session.serialize_handler") : $method;

        switch ($method) {
            case "php":
                return self::serializePhp($array, $safe);
                break;
            case "php_binary":
                return self::serializePhpbinary($array, $safe);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }

    /**
     * serialize by php
     * @param mixed $array 
     * @param bool $safe 
     * @return string 
     */
    public static function serializePhp($array, bool $safe = true)
    {
        if ($safe) {
            $array = unserialize(serialize($array));
        }

        $raw  = '';
        $line = 0;
        $keys = array_keys($array);

        foreach ($keys as $key) {
            $value = $array[$key];
            $line++;
            $raw .= $key . '|';
            if (is_array($value) && isset($value['huge_recursion_blocker_we_hope'])) {
                $raw .= 'R:' . $value['huge_recursion_blocker_we_hope'] . ';';
            } else {
                $raw .= serialize($value);
            }
            $array[$key] = ['huge_recursion_blocker_we_hope' => $line];
        }

        return $raw;
    }

    /**
     * serialize by phpbinary
     * @param mixed $array 
     * @param bool $safe 
     * @return string 
     */
    private static function serializePhpbinary($array, bool $safe = true)
    {
        return '';
    }

    /**
     * Decode
     * @param mixed $session_data 
     * @param string $method 
     * @return array 
     * @throws Exception 
     */
    public static function decode($session_data, $method = '')
    {
        $method = empty($method) ? ini_get("session.serialize_handler") : $method;

        switch ($method) {
            case "php":
                return self::unserializePhp($session_data);
                break;
            case "php_binary":
                return self::unserializePhpbinary($session_data);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }

    /**
     * unserialize by php
     * @param mixed $session_data 
     * @return array 
     * @throws Exception 
     */
    private static function unserializePhp($session_data)
    {
        $return_data = [];
        $offset      = 0;

        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("Invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos     = strpos($session_data, "|", $offset);
            $num     = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data                  = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }

        return $return_data;
    }

    /**
     * unserialize by phpbinary
     * @param mixed $session_data 
     * @return array 
     */
    private static function unserializePhpbinary($session_data)
    {
        $return_data = [];
        $offset      = 0;

        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data                  = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }

        return $return_data;
    }
}
