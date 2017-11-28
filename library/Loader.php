<?php
namespace deeka;

use deeka\Reflect;
use deeka\Log;

class Loader
{
    protected static $namespaces = [];
    protected static $maps       = [];

    const DS = '\\';

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
        $class    = ltrim($class, self::DS);
        // map
        if (isset(self::$maps[$class])) {
            include self::$maps[$class];
            return;
        }
        // 由右至左匹配
        $ns_prefix = $class;
        while (false !== $pos = strrpos($ns_prefix, self::DS)) {
            $ns_prefix = substr($ns_prefix, 0, $pos);
            $ns_index  = $ns_prefix . self::DS;
            if (isset(self::$namespaces[$ns_index])) {
                $ns_path = self::$namespaces[$ns_index];
                $ns_sub  = substr($class, $pos + 1);
                break;
            }
        }
        // 未匹配到
        if (!isset($ns_path)) {
            Log::write("ns_path is not exists", Log::EMERGENCY);
            return;
        }
        // Log::write("ns_prefix:{$ns_index}, ns_sub:{$ns_sub}, ns_path:{$ns_path}", Log::EMERGENCY);
        $path = $ns_path . $ns_sub . ".php";
        $path = strtr($path, self::DS, DS);
        if (is_file($path)) {
            include $path;
            // self::$maps[$class] = $path;
            return;
        }
        Log::write("{$path} is not exists", Log::EMERGENCY);
        return;
    }

    public static function addNamespace($name, string $path = ''): bool
    {
        if (is_array($name)) {
            foreach ($name as $ns => $path) {
                self::addNamespace($ns, $path);
            }
            return true;
        }
        // 兼容未以\结束的name
        $name = trim($name);
        $name = ltrim($name, self::DS);
        if (empty($name)) {
            return false;
        }
        if (self::DS != substr($name, -1, 1)) {
            $name .= self::DS;
        }
        self::$namespaces[$name] = $path;
        return true;
    }

    public static function addClassMap($class, $path = ''): bool
    {
        if (is_array($class)) {
            foreach ($class as $name => $path) {
                self::addClassMap($name, $path);
            }
            return true;
        }
        $class = trim($class);
        if (empty($class)) {
            return false;
        }
        self::$maps[$class] = $path;
        return true;
    }

    public static function register($autoload = '')
    {
        spl_autoload_register($autoload ? $autoload : [__CLASS__, 'autoload']);
    }

    public static function import(string $file = '')
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

    public static function controller(string $name, $args = [])
    {
        static $controller = [];
        $class             = $name;
        if (isset($controller[$class])) {
            return $controller[$class];
        }
        if (class_exists($class)) {
            is_string($args) && parse_str($args, $args);
            is_object($args) && $args = get_object_vars($args);
            $controller[$class]       = Reflect::invokeClass($class, $vars);
            return $controller[$class];
        }
        return false;
    }

    public static function action(string $controller_name, string $action_name, $args = [])
    {
        $controller = self::controller($controller_name);
        if ($controller) {
            is_string($args) && parse_str($args, $args);
            is_object($args) && $args = get_object_vars($args);
            return Reflect::invokeMethod([$controller, $action_name], $args);
        }
        return false;
    }
}
