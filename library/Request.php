<?php
namespace deeka;

class Request
{
    protected $mimeType = [
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

    private function method($case = CASE_UPPER)
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

    private function is($method = 'GET')
    {
        $method = strtoupper($method);
        switch ($method) {
            case 'MOBILE':
                return $this->isMobile();
                break;
            case 'SSL':
                return $this->isSsl();
                break;
            case 'AJAX':
                return $this->isAjax();
                break;
            case 'PJAX':
                return $this->isPjax();
                break;
            case 'CGI':
                return $this->isCgi();
                break;
            case 'CLI':
                return $this->isCli();
                break;
            default:
                return $this->method() == $method;
                break;
        }
        return false;
    }

    private function isPjax()
    {
        return Input::server('HTTP_X_PJAX') ? true : false;
    }

    private function isAjax()
    {
        return strtolower(Input::server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest' ? true : false;
    }

    private function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    private function isCli()
    {
        return PHP_SAPI == 'cli' ? true : false;
    }

    private function isGet()
    {
        return self::method() == 'GET';
    }

    private function isPost()
    {
        return self::method() == 'POST';
    }

    private function isPut()
    {
        return self::method() == 'PUT';
    }

    private function isDelete()
    {
        return self::method() == 'DELETE';
    }

    private function isOptions()
    {
        return self::method() == 'OPTIONS';
    }

    private function isPatch()
    {
        return self::method() == 'PATCH';
    }

    private function isHead()
    {
        return self::method() == 'HEAD';
    }

    private function isMobile()
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

    private function isSsl()
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

    private function scheme()
    {
        return self::isSsl() ? 'https' : 'http';
    }

    private function ip($type = 0)
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

    private function query()
    {
        return Input::server('QUERY_STRING');
    }

    private function host()
    {
        return Input::server('HTTP_HOST');
    }

    private function port()
    {
        return Input::server('SERVER_PORT');
    }

    private function protocol()
    {
        return Input::server('SERVER_PROTOCOL');
    }

    private function remotePort()
    {
        return Input::server('REMOTE_PORT');
    }

    private function header($name = '')
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

    private function type()
    {
        static $type = null;
        if (!is_null($type)) {
            return $type;
        }
        $accept = Input::server('HTTP_ACCEPT');
        $type   = false;
        if (!empty($accept)) {
            foreach ($this->mimeType as $key => $val) {
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

    private function module()
    {
        $var_name = Config::get('var.module', 'm');
        $default  = Config::get('default.module', 'index');
        $module   = trim(Input::request($var_name, $default));
        return $module ?? $default;
    }

    private function controller()
    {
        $var_name   = Config::get('var.controller', 'c');
        $default    = Config::get('default.controller', 'Index');
        $controller = ucfirst(trim(Input::request($var_name, $default)));
        return $controller ?? $default;
    }

    private function action()
    {
        $var_name = Config::get('var.action', 'a');
        $default  = Config::get('default.action', 'index');
        $action   = trim(Input::request($var_name, $default));
        return $action ?? $default;
    }

    private function time($float = false)
    {
        return $float ? Input::server('REQUEST_TIME_FLOAT') : Input::server('REQUEST_TIME');
    }

    private function contentType()
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

    private function content($parse = false)
    {
        static $content = null;
        static $data    = null;
        if (is_null($content)) {
            $content = file_get_contents('php://input');
        }
        if (is_null($data)) {
            if (false !== strpos($this->contentType(), 'application/json')) {
                $data = (array) json_decode($content, true);
            } else {
                parse_str($content, $data);
            }
        }
        return $parse ? $data : $content;
    }

    private function ext()
    {
        static $ext = null;
        if (is_null($ext)) {
            $ext = pathinfo($_SERVER['PATH_INFO'] ?? '/', PATHINFO_EXTENSION) ?? Input::param('_ext');
        }
        return $ext;
    }
}
