<?php
namespace deeka;

use deeka\traits\Singleton;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Reflect
{
    use Singleton;

    /**
     * 调用反射执行类的实例化 支持依赖注入
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeClass($class_name, $vars = [])
    {
        $reflect     = new ReflectionClass($class_name);
        $constructor = $reflect->getConstructor();
        if ($constructor) {
            if (!$constructor->isPublic()) {
                throw new Exception("Constructor of {$reflect->name} is not public in {$reflect->getFileName()} on line {$reflect->getStartLine()}", 1);
            }
            $args = self::bindParams($constructor, $vars);
            APP_DEBUG && Log::record("[RUN] {$constructor->class}::{$constructor->name}() in {$constructor->getFileName()} on line {$constructor->getStartLine()}", Log::INFO);
        }
        return $reflect->newInstanceArgs($args ?? []);
    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @param $method 方法
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeMethod($method, $vars = [])
    {
        if (is_array($method)) {
            $class   = is_object($method[0]) ? $method[0] : self::invokeClass($method[0]);
            $reflect = new ReflectionMethod($class, $method[1]);
            if (!$reflect->isPublic()) {
                throw new Exception("Method {$reflect->class}::{$reflect->name}() is not public in {$reflect->getFileName()} on line {$reflect->getStartLine()}", 1);
            }
        } else {
            $reflect = new ReflectionMethod($method);
        }
        $args = self::bindParams($reflect, $vars);
        APP_DEBUG && Log::record("[RUN] {$reflect->class}::{$reflect->name}() in {$reflect->getFileName()} on line {$reflect->getStartLine()}", Log::INFO);
        return $reflect->invokeArgs($class ?? null, $args);
    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @param $name 方法
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeFunction($name, $vars = [])
    {
        $reflect = new ReflectionFunction($name);
        $args    = self::bindParams($reflect, $vars);
        APP_DEBUG && Log::record("[RUN] {$reflect->name}() in {$reflect->getFileName()} on line {$reflect->getStartLine()}", Log::INFO);
        return $reflect->invokeArgs($args);
    }

    /**
     * 绑定参数
     * @param $reflect 反射类
     * @param array $vars 参数
     * @param $type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function bindParams($reflect, $vars = [])
    {
        $args = [];
        $c    = $reflect->getNumberOfParameters();

        if ($c) {
            reset($vars);
            $type   = self::isAssoc($vars) ? 0 : 1;
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
    public static function getParamValue($param, &$vars, $type)
    {
        $name  = $param->getName();
        $class = $param->getClass();

        if ($class) {
            $cn    = $class->getName();
            $value = method_exists($cn, 'instance') ? $cn::instance() : self::invokeClass($cn, $vars);
        } elseif (1 == $type && !empty($vars)) {
            $value = array_shift($vars);
        } elseif (0 == $type && isset($vars[$name])) {
            $value = $vars[$name];
        } elseif ($param->isDefaultValueAvailable()) {
            $value = $param->getDefaultValue();
        } else {
            throw new Exception("Method param \${$name} miss");
        }

        return $value;
    }

    /**
     * 是否关联数组
     * @param $array 数组
     * @return bool
     */
    public static function isAssoc($array): bool
    {
        if (!is_array($array)) {
            return false;
        }
        return array_values($array) != $array ? true : false;
    }
}
