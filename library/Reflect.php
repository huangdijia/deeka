<?php
namespace deeka;

use Exception;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Reflect
{
    /**
     * @param $method 反射类
     * @param array $vars 参数
     * @param $bind_type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeClass($class_name, $vars = [], $bind_type = 0)
    {
        if (method_exists($class_name, '__construct')) {
            $reflect = new ReflectionMethod($class_name, '__construct');
            if (!$reflect->isPublic()) {
                throw new Exception("{$reflect->class}::__construct() must be public method", 1);
            }
            $args = self::bindParams($reflect, $vars, $bind_type);
        }
        $class    = new ReflectionClass($class_name);
        $instance = $class->newInstanceArgs($args ?? []);
        return $instance;
    }

    /**
     * @param $method 反射方法
     * @param array $vars 参数
     * @param $bind_type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeMethod($method, $vars = [], $bind_type = 0)
    {
        if (is_array($method)) {
            $object  = is_object($method[0]) ? $method[0] : new $method[0]();
            $reflect = new ReflectionMethod($object, $method[1]);
            if (!$reflect->isPublic()) {
                throw new Exception("{$reflect->class}::{$method[1]}() must be public method", 1);
            }
        } else {
            $reflect = new ReflectionMethod($method);
        }
        $args = self::bindParams($reflect, $vars, $bind_type);
        return $reflect->invokeArgs($object ?? null, $args);
    }

    /**
     * @param $method 反射函数
     * @param array $vars 参数
     * @param $bind_type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeFunction($name, $args = [], $bind_type = 0)
    {
        $reflect = new ReflectionFunction($name);
        $args    = self::bindParams($reflect, $vars, $bind_type);
        return $reflect->invokeArgs($args);
    }

    /**
     * @param $reflect 反射对象
     * @param array $vars 参数
     * @param $bind_type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function bindParams($reflect, $vars = [], $bind_type = 0)
    {
        if (empty($vars)) {
            $vars = Input::param();
        }
        $args = [];
        if ($reflect->getNumberOfParameters() > 0) {
            // 判断数组类型 数字数组时按顺序绑定参数
            $params = $reflect->getParameters();
            foreach ($params as $param) {
                $name  = $param->getName();
                $class = $param->getClass();
                if ($class) {
                    $cn     = $class->getName();
                    $args[] = method_exists($cn, 'instance') ? $cn::instance() : self::invokeClass($cn, $vars);
                } elseif (1 == $bind_type && !empty($vars)) {
                    $args[] = array_shift($vars);
                } elseif (0 == $bind_type && isset($vars[$name])) {
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new Exception('Error param:' . $name);
                }
            }
        }
        return $args;
    }
}
