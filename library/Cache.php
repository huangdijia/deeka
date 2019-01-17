<?php
namespace deeka;

use deeka\traits\Singleton;
use Exception;

class Cache
{
    use Singleton;

    protected static $handlers = [];
    protected $options         = [
        'path'    => CACHE_PATH,
        'type'    => 'File',
        'path'    => '',
        'check'   => false,
        'prefix'  => 'cache_',
        'expire'  => 120,
        'timeout' => 0,
    ];
    protected static $methodMapping = [
        'rm' => 'delete',
    ];

    public static function __callStatic($name, $args)
    {
        return call_user_func_array([self::connect(), $name], $args);
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
        // return
        return $parse;
    }

    public static function connect($options = '')
    {
        if (empty($options)) {
            $options = Config::get('cache') ?? null;
        } elseif (is_scalar($options)) {
            $options = self::parse($options);
        } elseif (is_object($options)) {
            $options = (array) $options;
        }
        if (empty($options) || !is_array($options)) {
            throw new Exception("Error cache config.", 1);
        }
        // 合拼默认配置
        $options = array_merge(Config::get('cache'), $options);
        // 解析类型
        $type = $options['type'] ?? 'file';
        if (false === strpos($type, '\\')) {
            $class = '\\deeka\\cache\\driver\\' . ucfirst(strtolower($type));
        }
        // 驱动错误
        if (!class_exists($class)) {
            throw new Exception("Cache driver '{$class}' is not exists", 1);
        }
        // 解析key
        $key = md5($class . serialize($options));
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
}
