<?php
namespace deeka;

class Queue
{
    protected static $handlers = [];
    protected $options         = [
        'prefix' => 'queue_',
        'type'   => 'file',
        'path'   => DATA_PATH
    ];

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public static function parse($options = '')
    {
        if ($options === true || is_null($options)) {
            return [];
        }
        if (is_array($options) || is_object($options)) {
            return (array) $options;
        }
        if (is_numeric($options)) {
            $options = "expire={$options}";
        }
        // :file
        if (strpos($options, ':') === 0) {
            $options = "type=" . substr($options, 1);
        }
        // type=file&name=abc&expire=120&prefix=cache_&check=0
        if (preg_match('/[&=]/', $options)) {
            $parse = [];
            parse_str($options, $parse);
        }
        // type:file,name:abc,expire:120,prefix:cache_,check:0
        elseif (preg_match('/[:,]/', $options)) {
            $parse = [];
            if (preg_match_all('/([^:,]+)(?::([^:,]+))?/', $options, $matches)) {
                $parse = array_combine($matches[1], $matches[2]);
            }
        }
        return $parse;
    }

    public static function connect($options = '')
    {
        // 解析参数
        if (empty($options)) {
            $options = current(Config::get('queue')) ?? null;
        } elseif (is_scalar($options)) {
            $options = self::parse($options);
        } elseif (is_object($options)) {
            $options = (array) $options;
        }
        if (empty($options) || !is_array($options)) {
            throw new \Exception("Error queue config.", 1);
        }
        $type = $options['type'] ?? 'file';
        if (false === strpos($type, '\\')) {
            $class = '\\deeka\\queue\\driver\\' . ucfirst(strtolower($type));
        }
        // 驱动错误
        if (!class_exists($class)) {
            throw new \Exception("Error Queue Type '{$class}'", 1);
        }
        // 解析key
        $key = md5($class . join((array) $options));
        // 单例实例化
        if (
            !isset(self::$handlers[$key]) 
            || !is_object(self::$handlers[$key])
        ) {
            // 实例化驱动类
            self::$handlers[$key] = new $class($options);
        }
        // 返回对象
        return self::$handlers[$key];
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array([self::connect(), $name], $args);
    }
}
