<?php
namespace deeka;

use deeka\traits\Singleton;

// 例子
// 檢測$_GET參數
// $rules  = [
//     ['user_id', '會員ID不能為空', 'require'],
//     ['sex', '不接受變性人', 'in', 'boy,girl'],
//     ['age', '必須滿20歲', '>', 20],
//     ['mobile', '所填手機不是臺灣手機', 'twmobile'],
// ];
// if(!Validate::valid($_GET, $rules)){
//     $error  = Validate::getError();
//     die();
// }

/**
 * @method static bool require($variable)
 * @method static bool required($variable)
 * @method static bool regex($variable, $pattern)
 * @method static bool in($variable, array $haystack)
 * @method static bool notin($variable, array $haystack)
 * @method static bool between($variable, string|array $haystack)
 * @method static bool notbetween($variable, string|array $haystack)
 * @method static bool eq($variable, $value)
 * @method static bool equal($variable, $value)
 * @method static bool neq($variable, $value)
 * @method static bool notequal($variable, $value)
 * @method static bool gt($variable, $value)
 * @method static bool gte($variable, $value)
 * @method static bool lt($variable, $value)
 * @method static bool lte($variable, $value)
 * @method static bool length($variable, array|string $scopes)
 * @method static bool confirm($variable, string $varName)
 * @method static bool filter($variable, int $filter)
 * @method static bool contain($variable, mixed $needle )
 * @method static bool isDate($variable)
 * @method static bool isFunction($variable)
 * @method static bool isCallback($variable)
 * @package deeka
 */
class Validate
{
    use Singleton;

    // 預定義規則
    private static $rules = [];
    private static $error = '';

    // 魔法方法
    public static function __callStatic($name, $args)
    {
        if ('is' == substr($name, 0, 2)) {
            $rule      = strtolower(substr($name, 2));
            $value     = $args[0] ?? '';
            $condition = $args[1] ?? '';

            return self::check($value, $rule, $condition);
        }
    }

    /**
     * 规划格式：[變量名], [提示消息], [規則], [條件]
     * 
     * @param mixed $data 
     * @param array $rules 
     * @param bool $batch 
     * @return bool 
     */
    public static function valid($data, $rules = [], $batch = false)
    {
        if (is_array($rules) && !empty($rules)) {
            // 批量检测错误
            $errors = [];
            // 遍历检测
            foreach ($rules as $item) {
                // 空数组不验证
                if (!isset($item[0])) {
                    continue;
                }
                // 准备数据
                $var_name  = $item[0];
                $rule      = $item[2] ?? 'require';
                $condition = $item[3] ?? '';
                $error     = $item[1] ?? '' ?? "參數[{$var_name}]不正確";
                // 必須檢測
                if ($rule == 'require' && !isset($data[$var_name])) {
                    $data[$var_name] = '';
                }
                // 不存在不檢測
                if (!isset($data[$var_name])) {
                    continue;
                }
                // 設置數值
                $value = $data[$var_name];
                // 按規則驗證
                if (!self::check($value, $rule, $condition)) {
                    if (!$batch) {
                        // 直接返回错误
                        self::$error = $error;
                        return false;
                    } else {
                        // 只保留第一条错误
                        if (!isset($errors[$var_name])) {
                            $errors[$var_name] = $error;
                        }
                    }
                }
            }
        }

        // 返回批量验证结果[array]
        if ($batch && count($errors) > 0) {
            self::$error = $errors;
            return false;
        }

        // 重置錯誤
        self::$error = '';

        return true;
    }

    /**
     * Check by any rule
     * @param mixed $value 
     * @param string $rule 
     * @param string $condition 
     * @return bool 
     */
    public static function check($value, $rule = 'require', $condition = '')
    {
        // 規則轉換
        if (isset(self::$rules[$rule])) {
            $condition = self::$rules[$rule];
            $rule      = 'regex';
        }

        // 根据规则检测
        switch ($rule) {
            case 'required':
            case 'require': // @method static bool require($variable)
                if (empty($value)) {
                    return false;
                }
                break;
            case 'regex': // @method static bool regex($variable, $pattern)
                if (!empty($condition) && !preg_match($condition, $value)) {
                    return false;
                }
                break;
            case 'in': // @method static bool in($variable, array $haystack)
                $range = is_array($condition) ? $condition : explode(',', $condition);
                if (!in_array($value, $range)) {
                    return false;
                }
                break;
            case 'notin': // @method static bool notin($variable, array $haystack)
                $range = is_array($condition) ? $condition : explode(',', $condition);
                if (!!in_array($value, $range)) {
                    return false;
                }
                break;
            case 'between': // @method static bool between($variable, string|array $haystack)
                if (is_array($condition) && count($condition) > 1) {
                    list($min, $max) = $condition;
                } else {
                    list($min, $max) = explode(',', $condition);
                }
                if (!($min <= $value && $value <= $max)) {
                    return false;
                }
                break;
            case 'notbetween': // @method static bool notbetween($variable, string|array $haystack)
                if (is_array($condition)) {
                    list($min, $max) = $condition;
                } else {
                    list($min, $max) = explode(',', $condition);
                }
                if (($min <= $value && $value <= $max)) {
                    return false;
                }
                break;
            case 'eq': // @method static bool eq($variable, $value)
            case 'equal': // @method static bool equal($variable, $value)
            case '=':
                if (!($value == $condition)) {
                    return false;
                }
                break;
            case 'neq': // @method static bool neq($variable, $value)
            case 'notequal': // @method static bool notequal($variable, $value)
            case '!=':
            case '<>':
                if (!($value != $condition)) {
                    return false;
                }
                break;
            case 'gt': // @method static bool gt($variable, $value)
            case '>':
                if (!($value > $condition)) {
                    return false;
                }
                break;
            case 'gte': // @method static bool gte($variable, $value)
            case '>=':
                if (!($value >= $condition)) {
                    return false;
                }
                break;
            case 'lt': // @method static bool lt($variable, $value)
            case '<':
                if (!($value < $condition)) {
                    return false;
                }
                break;
            case 'lte': // @method static bool lte($variable, $value)
            case '<=':
                if (!($value <= $condition)) {
                    return false;
                }
                break;
            case 'length': // @method static bool length($variable, array|string $scopes)
                $length = strlen($value);
                if (false !== strpos($condition, ',')) {
                    list($min, $max) = explode(',', $condition);
                    if (!($min <= $length && $length <= $max)) {
                        return false;
                    }
                } else {
                    if (!($length == $condition)) {
                        return false;
                    }
                }
                break;
            case 'confirm': // @method static bool confirm($variable, mixed $data)
                $data = Input::param($condition);

                if (isset($data[$condition])) {
                    if (!($value == $data[$condition])) {
                        return false;
                    }
                }
                break;
            case 'filter': // @method static bool filter($variable, int $filter)
                if (!filter_var($value, $condition)) {
                    return false;
                }
                break;
            case 'contain': // @method static bool contain($variable, mixed $needle )
                if (!(0 < stripos($value, $condition))) {
                    return false;
                }
                break;
            case 'date': // @method static bool isDate($variable)
                list($year, $month, $day) = [substr($value, 0, 4), substr($value, 5, 2), substr($value, 8, 2)];

                if (!checkdate($month, $day, $year)) {
                    return false;
                }

                break;
            case 'function': // @method static bool isFunction($variable)
            case 'callback': // @method static bool isCallback($variable)
                if (!is_callable($value)) {
                    return false;
                }

                return call_user_func_array($value, []) ? true : false;
                break;
        }

        return true;
    }

    /**
     * Add rule
     * @param mixed $rule 
     * @param string $regex 
     * @return void 
     */
    public static function addRule($rule, $regex = '')
    {
        if (is_array($rule)) {
            self::$rules = array_merge((array) self::$rules, (array) $rule);
        } else {
            self::$rules[$rule] = $regex;
        }
    }

    /**
     * Get error
     * @return string 
     */
    public static function getError()
    {
        return self::$error;
    }
}

Validate::addRule([
    'require'  => '/.+/',
    'email'    => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
    'url'      => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
    'currency' => '/^\d+(\.\d+)?$/',
    'number'   => '/^\d+$/',
    'zip'      => '/^\d{6}$/',
    'integer'  => '/^[-\+]?\d+$/',
    'double'   => '/^[-\+]?\d+(\.\d+)?$/',
    'english'  => '/^[A-Za-z]+$/',
    'chinese'  => '/^[\x{4e00}-\x{9fa5}]+$/u',
    'unicode'  => '/^[\u2E80-\u9FFF]+$/',
    'ipv4'     => '/^((25[0-5]|2[0-4]\d|[0-1]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[0-1]?\d\d?)$/',
    'ipv6'     => '/^\s*((([0-9A-Fa-f]{1,4}:){7}(([0-9A-Fa-f]{1,4})|:))|(([0-9A-Fa-f]{1,4}:){6}(:|((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})|(:[0-9A-Fa-f]{1,4})))|(([0-9A-Fa-f]{1,4}:){5}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){4}(:[0-9A-Fa-f]{1,4}){0,1}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){3}(:[0-9A-Fa-f]{1,4}){0,2}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:){2}(:[0-9A-Fa-f]{1,4}){0,3}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(([0-9A-Fa-f]{1,4}:)(:[0-9A-Fa-f]{1,4}){0,4}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(:(:[0-9A-Fa-f]{1,4}){0,5}((:((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})?)|((:[0-9A-Fa-f]{1,4}){1,2})))|(((25[0-5]|2[0-4]\d|[01]?\d{1,2})(\.(25[0-5]|2[0-4]\d|[01]?\d{1,2})){3})))(%.+)?\s*$/',
    'cntel'    => '',
    'twtel'    => '',
    'hktel'    => '',
    // 'cnmobile' => '/^(00){0,1}(86){0,1}0{0,1}13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/',
    'cnmobile' => '/^(00){0,1}(86){0,1}0{0,1}1[3-9]{1}[0-9]{9}$/',
    'twmobile' => '/^(00){0,1}(886){0,1}0{0,1}(?:09\d{8})$/',
    'hkmobile' => '/^(00){0,1}(852){0,1}0{0,1}(?:\d{7}|\d{8}|\d{12})$/',
    'cnidcard' => '/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}(\d|x|X)$/',
    'twidcard' => '/^[a-zA-Z][1-2]\d{9}$/',
    'hkidcard' => '/^[A-Z][0-9]{6}\([0-9A]\)$/',
    'moidcard' => '/^[157][0-9]{6}\([0-9]\)$/',
]);
