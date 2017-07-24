<?php
namespace deeka;

class Request
{
    protected static $mimeType = [
        'html' => 'text/html,application/xhtml+xml,*/*',
        'xml'  => 'application/xml,text/xml,application/x-xml',
        'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'   => 'text/javascript,application/javascript,application/x-javascript',
        'css'  => 'text/css',
        'rss'  => 'application/rss+xml',
        'yaml' => 'application/x-yaml,text/yaml',
        'atom' => 'application/atom+xml',
        'pdf'  => 'application/pdf',
        'text' => 'text/plain',
        'png'  => 'image/png',
        'jpg'  => 'image/jpg,image/jpeg,image/pjpeg',
        'gif'  => 'image/gif',
        'csv'  => 'text/csv',
    ];

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public static function method($case = CASE_UPPER)
    {
        switch ($case) {
            case CASE_LOWER:
                return strtolower(Input::server('REQUEST_METHOD'));
                break;
            case CASE_UPPER:
            default:
                return strtoupper(Input::server('REQUEST_METHOD'));
                break;
        }
    }

    public static function is($method = 'GET')
    {
        $method = strtoupper($method);
        switch ($method) {
            case 'MOBILE':
                return self::isMobile();
                break;
            case 'SSL':
                return self::isSsl();
                break;
            case 'AJAX':
                return self::isAjax();
                break;
            case 'PJAX':
                return self::isPjax();
                break;
            case 'CGI':
                return self::isCgi();
                break;
            case 'CLI':
                return self::isCli();
                break;
            default:
                return self::method() == $method;
                break;
        }
        return false;
    }

    public static function isPjax()
    {
        return Input::server('HTTP_X_PJAX') ? true : false;
    }

    public static function isAjax()
    {
        return strtolower(Input::server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest' ? true : false;
    }

    public static function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    public static function isCli()
    {
        return PHP_SAPI == 'cli' ? true : false;
    }

    public static function isGet()
    {
        return self::method() == 'GET';
    }

    public static function isPost()
    {
        return self::method() == 'POST';
    }

    public static function isPut()
    {
        return self::method() == 'PUT';
    }

    public static function isDelete()
    {
        return self::method() == 'DELETE';
    }

    public static function isOptions()
    {
        return self::method() == 'OPTIONS';
    }

    public static function isPatch()
    {
        return self::method() == 'PATCH';
    }

    public static function isHead()
    {
        return self::method() == 'HEAD';
    }

    public function isMobile()
    {
        static $mobile = null;
        if (!is_null($mobile)) {
            return $mobile;
        }
        if (
            stristr(Input::server('HTTP_VIA'), "wap")
            || strpos(strtoupper(Input::server('HTTP_ACCEPT')), "VND.WAP.WML")
            || Input::server('HTTP_PROFILE')
            || preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', Input::server('HTTP_USER_AGENT'))
        ) {
            $mobile = true;
        } else {
            $mobile = false;
        }
        return $mobile;
    }

    public static function isSsl()
    {
        static $ssl = null;
        if (!is_null($ssl)) {
            return $ssl;
        }
        $ssl = false;
        if (
            in_array(Input::server('HTTPS'), ['1', 'on'])
            || 'https' == Input::server('REQUEST_SCHEME')
            || 'https' == Input::server('HTTP_X_FORWARDED_PROTO')
            || '443' == Input::server('SERVER_PORT')
        ) {
            $ssl = true;
        }
        return $ssl;
    }

    public static function scheme()
    {
        return self::isSsl() ? 'https' : 'http';
    }

    public static function ip($type = 0)
    {
        $type      = $type ? 1 : 0;
        static $ip = null;
        if (!is_null($ip)) {
            return $ip[$type];
        }
        if ('' != Input::server('HTTP_X_FORWARDED_FOR')) {
            $arr = explode(',', Input::server('HTTP_X_FORWARDED_FOR'));
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif ('' != Input::server('HTTP_CLIENT_IP')) {
            $ip = Input::server('HTTP_CLIENT_IP');
        } elseif ('' != Input::server('REMOTE_ADDR')) {
            $ip = Input::server('REMOTE_ADDR');
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }

    public static function query()
    {
        return Input::server('QUERY_STRING');
    }

    public static function host()
    {
        return Input::server('HTTP_HOST');
    }

    public static function port()
    {
        return Input::server('SERVER_PORT');
    }

    public static function protocol()
    {
        return Input::server('SERVER_PROTOCOL');
    }

    public static function remotePort()
    {
        return Input::server('REMOTE_PORT');
    }

    public static function header($name = '')
    {
        static $header = null;
        if (is_null($header)) {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
            $header = array_change_key_case($header);
        }
        if ('' === $name) {
            return $header;
        }
        $name = str_replace('_', '-', strtolower($name));
        return $header[$name] ?? null;
    }

    public static function type()
    {
        static $type = null;
        if (!is_null($type)) {
            return $type;
        }
        $accept = Input::server('HTTP_ACCEPT');
        $type   = false;
        if (!empty($accept)) {
            foreach (self::$mimeType as $key => $val) {
                $array = explode(',', $val);
                foreach ($array as $k => $v) {
                    if (stristr($accept, $v)) {
                        $type = $key;
                        break;
                    }
                }
            }
        }
        return $type;
    }

    public static function module()
    {
        $var_name = Config::get('var.module', 'm');
        $default  = Config::get('default.module', 'index');
        $module   = trim(Input::request($var_name, $default));
        return $module ?? $default;
    }

    public static function controller()
    {
        $var_name   = Config::get('var.controller', 'c');
        $default    = Config::get('default.controller', 'Index');
        $controller = ucfirst(trim(Input::request($var_name, $default)));
        return $controller ?? $default;
    }

    public static function action()
    {
        $var_name = Config::get('var.action', 'a');
        $default  = Config::get('default.action', 'index');
        $action   = trim(Input::request($var_name, $default));
        return $action ?? $default;
    }

    public static function time($float = false)
    {
        return $float ? Input::server('REQUEST_TIME_FLOAT') : Input::server('REQUEST_TIME');
    }

    public static function contentType()
    {
        static $type = null;
        if (!is_null($type)) {
            return $type;
        }
        $contentType = Input::server('CONTENT_TYPE');
        if ($contentType) {
            if (strpos($contentType, ';')) {
                $type = explode(';', $contentType)[0];
            } else {
                $type = $contentType;
            }
            return trim($type);
        }
        return '';
    }

    public static function content($parse = false)
    {
        static $content = null;
        static $data    = null;
        if (is_null($content)) {
            $content = file_get_contents('php://input');
        }
        if (is_null($data)) {
            if (false !== strpos(self::contentType(), 'application/json')) {
                $data = (array) json_decode($content, true);
            } else {
                parse_str($content, $data);
            }
        }
        return $parse ? $data : $content;
    }


    public static function ext()
    {
        static $ext = null;
        if (is_null($ext)) {
            $ext = pathinfo($this->pathinfo(), PATHINFO_EXTENSION) ?? Input::param('_ext');
        }
        return $ext;
    }
}
