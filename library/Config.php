<?php
namespace deeka;

class Config
{
    const DELIMITER          = '.';
    private static $_config  = [];
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

    private function all()
    {
        return self::$_config;
    }

    private function set($name = '', $value = null)
    {
        // Object to Array
        if (is_object($name)) {
            $name = get_object_vars($name);
        }
        // 批量导入 Config::set([]);
        if (is_array($name) && !empty($name)) {
            $name = (array) self::arrayChangeKeyCase($name);
            foreach ($name as $key => $value) {
                self::set($key, $value);
            }
            return true;
        }
        // 错误类型
        if (!is_string($name)) {
            throw new \Exception("Error type of \$name of " . __METHOD__ . "()", 1);
        }
        if (empty($name)) {
            throw new \Exception("\$name cannot be empty", 1);
        }
        // 单个设置
        $name = strtolower($name);
        $keys = explode(self::DELIMITER, $name);
        $tmp  = &self::$_config;
        foreach ($keys as $key) {
            if (!isset($tmp[$key])) {
                $tmp[$key] = [];
            }
            $tmp = &$tmp[$key];
        }
        // 兼容 array
        if (is_array($tmp) && is_array($value)) {
            $tmp = array_merge($tmp, $value);
        } else {
            $tmp = $value;
        }
        return true;
    }

    private function has(string $name = '')
    {
        // 错误类型
        if (empty($name)) {
            throw new \Exception("\$name cannot be empty", 1);
        }
        $name  = strtolower($name);
        $names = explode(self::DELIMITER, $name);
        $tmp   = self::$_config;
        foreach ($names as $key) {
            if (isset($tmp[$key])) {
                $tmp = $tmp[$key];
            } else {
                return false;
            }
        }
        return true;
    }

    private function get(string $name = '', $default = null)
    {
        // 返回所有配置
        if (empty($name)) {
            return self::all();
        }
        // 转小写
        $name = strtolower($name);
        $keys = explode(self::DELIMITER, $name);
        $tmp  = self::$_config;
        // 分层获取
        foreach ($keys as $key) {
            // 存在则返回
            if (isset($tmp[$key])) {
                $tmp = $tmp[$key];
            } else {
                // 记录日志
                Log::record("config {$name} is undefined.", Log::NOTICE);
                // 不存在则返回传入默认值
                return $default;
            }
        }
        return $tmp;
    }

    private static function arrayChangeKeyCase(array $vars = [], int $case = CASE_LOWER)
    {
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                $value = get_object_vars($value);
            }
            if (is_array($value)) {
                $vars[$key] = self::arrayChangeKeyCase($value, $case);
            }
        }
        return array_change_key_case($vars, $case);
    }
}
