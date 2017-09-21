<?php
namespace deeka;

use Exception;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class Reflect
{
    /**
     * 调用反射执行类的实例化 支持依赖注入
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeClass($class_name, $vars = [], $type = 0)
    {
        $args = [];
        // invoke __construct but not execute
        if (method_exists($class_name, '__construct')) {
            [, $args] = self::bindMethodParams([$class_name, '__construct'], $vars, $type);
        }
        // creating an instance
        $reflect  = new ReflectionClass($class_name);
        $instance = $reflect->newInstanceArgs($args);
        // return instance
        return $instance;
    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @param $method 方法
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeMethod($method, $vars = [], $type = 0)
    {
        [$reflect, $args] = self::bindMethodParams($method, $vars, $type);
        APP_DEBUG && Log::record("[RUN] {$reflect->class}->{$reflect->name}() in {$reflect->getFileName()} on line {$reflect->getStartLine()}", Log::INFO);
        $object = (isset($method[0]) && is_object($method[0])) ? $method[0] : null;
        return $reflect->invokeArgs($object, $args);
    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @param $name 方法
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeFunction($name, $vars = [], $type = 0)
    {
        $reflect = new ReflectionFunction($name);
        $args    = self::bindParams($reflect, $vars, $type);
        APP_DEBUG && Log::record("[RUN] {$reflect->name}() in {$reflect->getFileName()} on line {$reflect->getStartLine()}", Log::INFO);
        return $reflect->invokeArgs($args);
    }

    /**
     * 方法参数注入
     * @param $method 方法
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return array
     */
    public static function bindMethodParams($method, $vars = [], $type = 0)
    {
        if (is_array($method)) {
            $reflect = new ReflectionMethod($method[0], $method[1]);
            if (!$reflect->isPublic()) {
                throw new Exception("{$reflect->class}::{$method[1]}() must be public method", 1);
            }
        } else {
            $reflect = new ReflectionMethod($method);
        }
        $args = self::bindParams($reflect, $vars, $type);
        return [$reflect, $args];
    }

    /**
     * 绑定参数
     * @param $reflect 反射类
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function bindParams($reflect, $vars = [], $type = 0)
    {
        $args = [];
        $argc = $reflect->getNumberOfParameters();
        if ($argc) {
            // 判断数组类型 数字数组时按顺序绑定参数
            $params = $reflect->getParameters();
            foreach ($params as $param) {
                $args[] = self::getParamValue($param, $vars, $type);
            }
        }
        return $args;
    }

    /**
     * 获取参数值
     * @param $reflect 反射类
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function getParamValue($param, & $vars, $type = 0)
    {
        $name  = $param->getName();
        $class = $param->getClass();
        if ($class) {
            $cn   = $class->getName();
            $argv = method_exists($cn, 'instance') ? $cn::instance() : self::invokeClass($cn, $vars);
        } elseif (1 == $type && !empty($vars)) {
            $argv = array_shift($vars);
        } elseif (0 == $type && isset($vars[$name])) {
            $argv = $vars[$name];
        } elseif ($param->isDefaultValueAvailable()) {
            $argv = $param->getDefaultValue();
        } else {
            throw new Exception("Method param \${$name} miss");
        }
        return $argv;
    }
}
