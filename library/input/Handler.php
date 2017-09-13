<?php
namespace deeka\input;

use deeka\Request;

class Handler
{
    // 全局过滤规则
    protected static $filter = null;

    public function server($name = '', $default = null, $filters = '')
    {
        return $this->input($_SERVER ?? [], $name, $default, $filters);
    }

    public function env($name = '', $default = null, $filters = '')
    {
        return $this->input($_ENV ?? [], $name, $default, $filters);
    }

    public function cookie($name = '', $default = null, $filters = '')
    {
        return $this->input($_COOKIE ?? [], $name, $default, $filters);
    }

    public function session($name = '', $default = null, $filters = '')
    {
        return $this->input($_SESSION ?? [], $name, $default, $filters);
    }

    public function globals($name = '', $default = null, $filters = '')
    {
        return $this->input($GLOBALS, $name, $default, $filters);
    }

    public function request($name = '', $default = null, $filters = '')
    {
        if (empty($_POST)) {
            $_POST = Request::content(true);
        }
        $_REQUEST = array_merge($_POST, $_GET);
        $input    = $_REQUEST;
        return $this->input($input ?? [], $name, $default, $filters);
    }

    public function get($name = '', $default = null, $filters = '')
    {
        return $this->input($_GET ?? [], $name, $default, $filters);
    }

    public function post($name = '', $default = null, $filters = '')
    {
        if (empty($_POST)) {
            $_POST = Request::content(true);
        }
        return $this->input($_POST ?? [], $name, $default, $filters);
    }

    public function put($name = '', $default = null, $filters = '')
    {
        $input = Request::content(true);
        return $this->input($input, $name, $default, $filters);
    }

    public function param($name = '', $default = null, $filters = '')
    {
        switch (strtolower(self::server('REQUEST_METHOD'))) {
            case 'put':
                $input = Request::content(true);
                break;
            case 'post':
                if (empty($_POST)) {
                    $_POST = Request::content(true);
                }
                $input = $_POST;
                break;
            case 'delete':
            case 'get':
            case 'cli':
            default:
                $input = $_GET;
                break;
        }
        return $this->input($input, $name, $default, $filters);
    }

    public function delete($name = '', $default = null, $filters = '')
    {
        return $this->input($_GET ?? [], $name, $default, $filters);
    }

    protected function input($input = [], $name = '', $default = null, $filters = '')
    {
        // 变量全局过滤
        $input = self::arrayMapRecursive('self::filterExp', $input);
        // 自定义过滤
        if (!empty(self::$filter)) {
            $_filters = self::$filter;
            if (is_string($_filters)) {
                $_filters = explode(',', self::$filter);
            }
            // 全局参数过滤
            foreach ($_filters as $_filter) {
                $_filter = trim($_filter);
                $input   = self::arrayMapRecursive($_filter, $input);
            }
        }
        // 返回全部参数
        if (!isset($name) || '' == $name) {
            return $input;
        }
        // 变量不存在则返回默认值
        if (!isset($input[$name])) {
            return $default;
        }
        // 返回变量
        $data = $input[$name];
        // 变量过滤
        if (!empty($filters)) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ((array) $filters as $filter) {
                $filter = trim($filter);
                if (is_callable($filter)) {
                    $data = is_array($data) ? self::arrayMapRecursive($filter, $data) : $filter($data);
                } else {
                    $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $data) {
                        return $default;
                    }
                }
            }
        }
        return $data;
    }

    // 设置过滤规则
    public static function setGlobalFilter($filter = '')
    {
        self::$filter = $filter;
    }

    // 过滤表单中的表达式
    public static function filterExp($value)
    {
        if (in_array($value, ['exp', 'or'])) {
            $value .= ' ';
        }
        return $value;
    }

    public static function arrayMapRecursive($filter, $data)
    {
        $result = [];
        foreach ((array) $data as $key => $val) {
            if (is_array($val)) {
                $result[$key] = self::arrayMapRecursive($filter, $val);
            } else {
                $result[$key] = call_user_func($filter, $val);
            }
        }
        return $result;
    }
}