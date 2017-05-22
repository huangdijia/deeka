<?php
namespace deeka;

/*
 * Input::setGlobalFilter('addslashes, htmlspecialchars');
 * Input::$fun([key], [default], [filter])
 */
class Input
{
    // 全局过滤规则
    protected static $filter = null;

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    // 设置过滤规则
    public static function setGlobalFilter($filter = '')
    {
        self::$filter = $filter;
    }

    // 静态方法
    public static function __callStatic($name, $args = [])
    {
        switch (strtolower($name)) {
            case 'server':
                $input = $_SERVER;
                break;
            case 'env':
                $input = $_ENV;
                break;
            case 'cookie':
                $input = $_COOKIE;
                break;
            case 'session':
                $input = $_SESSION;
                break;
            case 'globals':
                $input = $GLOBALS;
                break;
            case 'request':
                $_REQUEST = array_merge($_POST, $_GET);
                $input    = $_REQUEST;
                break;
            case 'get':
            case 'delete':
                $input = $_GET;
                break;
            case 'post':
                $input = $_POST;
                break;
            case 'put':
                parse_str(file_get_contents('php://input'), $input);
                break;
            case 'param':
                switch (strtolower(self::server('REQUEST_METHOD'))) {
                    case 'put':
                        parse_str(file_get_contents('php://input'), $input);
                        break;
                    case 'post':
                        $input = $_POST;
                        break;
                    case 'delete':
                    case 'get':
                    case 'cli':
                    default:
                        $input = $_GET;
                        break;
                }
                break;
            default:
                return null;
        }
        // 默认返回值
        $default = isset($args[1]) ? $args[1] : null;
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
        if (!isset($args[0]) || '' == $args[0]) {
            return $input;
        }
        // 变量不存在则返回默认值
        if (!isset($input[$args[0]])) {
            return $default;
        }
        // 返回变量
        $data = $input[$args[0]];
        // 变量过滤
        $filters = isset($args[2]) ? $args[2] : '';
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
