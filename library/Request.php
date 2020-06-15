<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;

/**
 * @method static string method($case = CASE_UPPER)
 * @method static bool is($method = 'GET')
 * @method static bool isPjax()
 * @method static bool isAjax()
 * @method static bool isCgi()
 * @method static bool isCli()
 * @method static bool isGet()
 * @method static bool isPost()
 * @method static bool isPut()
 * @method static bool isDelete()
 * @method static bool isOptions()
 * @method static bool isPatch()
 * @method static bool isHead()
 * @method static bool isMobile()
 * @method static bool isSsl()
 * @method static string scheme()
 * @method static string|int ip($type = 0)
 * @method static string query()
 * @method static string host()
 * @method static string|int port()
 * @method static string protocol()
 * @method static string|int remotePort()
 * @method static string|array header($name = '')
 * @method static string type()
 * @method static string module()
 * @method static string controller()
 * @method static string action()
 * @method static string|int time($float = false)
 * @method static string contentType()
 * @method static array|null|string|false content($parse = false)
 * @method static string ext()
 * @package deeka
 */
class Request
{
    // use Singleton;
    use SingletonCallable;
    use SingletonInstance;

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

    /**
     * Get request method
     * @param int $case 
     * @return string 
     */
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

    /**
     * Is xx method
     * @param string $method 
     * @return bool 
     */
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

    /**
     * Is pjax
     * @return bool 
     */
    private function isPjax()
    {
        return Input::server('HTTP_X_PJAX') ? true : false;
    }

    /**
     * Is ajax
     * @return bool 
     */
    private function isAjax()
    {
        return strtolower(Input::server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest' ? true : false;
    }

    /**
     * Is cgi
     * @return bool 
     */
    private function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * Is cli
     * @return bool 
     */
    private function isCli()
    {
        return PHP_SAPI == 'cli' ? true : false;
    }

    /**
     * Is get
     * @return bool 
     */
    private function isGet()
    {
        return self::method() == 'GET';
    }

    /**
     * Is post
     * @return bool 
     */
    private function isPost()
    {
        return self::method() == 'POST';
    }

    /**
     * Is put
     * @return bool 
     */
    private function isPut()
    {
        return self::method() == 'PUT';
    }

    /**
     * Is delete
     * @return bool 
     */
    private function isDelete()
    {
        return self::method() == 'DELETE';
    }

    /**
     * Is options
     * @return bool 
     */
    private function isOptions()
    {
        return self::method() == 'OPTIONS';
    }

    /**
     * Is patch
     * @return bool 
     */
    private function isPatch()
    {
        return self::method() == 'PATCH';
    }

    /**
     * Is head
     * @return bool 
     */
    private function isHead()
    {
        return self::method() == 'HEAD';
    }

    /**
     * Is mobile
     * @return bool 
     */
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

    /**
     * Is ssl
     * @return bool 
     */
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

    /**
     * Get scheme
     * @return string 
     */
    private function scheme()
    {
        return self::isSsl() ? 'https' : 'http';
    }

    /**
     * Get ip
     * @param int $type 
     * @return int|string 
     */
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

    /**
     * Get query
     * @return string 
     */
    private function query()
    {
        return Input::server('QUERY_STRING');
    }

    /**
     * Get host
     * @return string 
     */
    private function host()
    {
        return Input::server('HTTP_HOST');
    }

    /**
     * Get port
     * @return string 
     */
    private function port()
    {
        return Input::server('SERVER_PORT');
    }

    /**
     * Get protocol
     * @return mixed 
     */
    private function protocol()
    {
        return Input::server('SERVER_PROTOCOL');
    }

    /**
     * Get remote addr
     * @return string 
     */
    private function remotePort()
    {
        return Input::server('REMOTE_PORT');
    }

    /**
     * Get headers or some one
     * @param string $name 
     * @return mixed 
     */
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

    /**
     * Get http type
     * @return string|int|false 
     */
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

    /**
     * Get module
     * @return string 
     */
    private function module()
    {
        $var_name = Config::get('var.module', 'm');
        $default  = Config::get('default.module', 'index');
        $module   = trim(Input::request($var_name, $default));
        return $module ?? $default;
    }

    /**
     * Get controller
     * @return string 
     */
    private function controller()
    {
        $var_name   = Config::get('var.controller', 'c');
        $default    = Config::get('default.controller', 'Index');
        $controller = ucfirst(trim(Input::request($var_name, $default)));
        return $controller ?? $default;
    }

    /**
     * Get action
     * @return mixed 
     */
    private function action()
    {
        $var_name = Config::get('var.action', 'a');
        $default  = Config::get('default.action', 'index');
        $action   = trim(Input::request($var_name, $default));
        return $action ?? $default;
    }

    /**
     * Get time or timestamps
     * @param bool $float 
     * @return mixed 
     */
    private function time($float = false)
    {
        return $float ? Input::server('REQUEST_TIME_FLOAT') : Input::server('REQUEST_TIME');
    }

    /**
     * Get content type
     * @return string 
     */
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

    /**
     * Get content
     * @param bool $parse 
     * @return array|null|string|false 
     */
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

    /**
     * Get extension
     * @return mixed 
     */
    private function ext()
    {
        static $ext = null;

        if (is_null($ext)) {
            $ext = pathinfo($_SERVER['PATH_INFO'] ?? '/', PATHINFO_EXTENSION) ?? Input::param('_ext');
        }

        return $ext;
    }
}
