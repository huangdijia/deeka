<?php
namespace deeka;

class Loader
{
    protected static $namespaces = [];
    protected static $maps       = [];

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public static function autoload($class)
    {
        $class = ltrim($class, '\\');
        // map
        if (isset(self::$maps[$class])) {
            include self::$maps[$class];
            return;
        }
        // namespace
        list($root_ns, $sub_ns) = explode('\\', $class, 2);
        if (isset(self::$namespaces[$root_ns])) {
            $ns_path = self::$namespaces[$root_ns];
        } else {
            throw new \Exception("NAMESPACE ROOT {$root_ns} NOT DEFINED", 1);
        }
        $path = $ns_path . $sub_ns . ".php";
        $path = strtr($path, '\\', DS);
        if (is_file($path)) {
            include $path;
            self::$maps[$class] = $path;
            return;
        } else {
            error_log($path . "\n", 3, LOG_PATH . 'loader.log');
        }
        return;
    }

    public static function addNamespace($name, $path = '')
    {
        if (is_array($name)) {
            foreach ($name as $ns => $path) {
                self::addNamespace($ns, $path);
            }
        } else {
            self::$namespaces[$name] = $path;
        }
    }

    public static function addClassMap($class, $path = '')
    {
        if (is_array($class)) {
            foreach ($class as $name => $path) {
                self::addClassMap($name, $path);
            }
        } else {
            self::$maps[$class] = $path;
        }
    }

    public static function register($autoload = '')
    {
        spl_autoload_register($autoload ? $autoload : [__CLASS__, 'autoload']);
    }

    public static function import($file)
    {
        static $files = [];
        if (isset($files[$file])) {
            return $files[$file];
        }
        if (is_file($file)) {
            $files[$file] = include $file;
        } else {
            $files[$file] = false;
        }
        return $files[$file];
    }

    public static function controller($name)
    {
        static $controller = [];
        $class             = $name;
        if (isset($controller[$class])) {
            return $controller[$class];
        }
        if (class_exists($class)) {
            $controller[$class] = new $class;
            return $controller[$class];
        }
        return false;
    }

    public static function action($controller_name, $action_name, $args = [])
    {
        $class = self::controller($controller_name);
        if ($class) {
            if (is_string($args)) {
                parse_str($args, $args);
            }
            return call_user_func_array([$class, $action_name], $args);
        }
        return false;
    }
}
