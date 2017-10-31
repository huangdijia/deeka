<?php
namespace deeka;

use deeka\response\Json as JsonResponse;
use deeka\response\Jsonp as JsonpResponse;
use deeka\response\Xml as XmlResponse;

class Response
{
    protected static $handler = null;
    protected $headers        = [];
    protected $body           = '';
    protected $status         = 200;
    protected $options        = [];
    public static $codes      = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    private function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    private function __clone()
    {
        //
    }

    public static function instance($options = [])
    {
        $class = get_called_class();
        if (!empty(self::$handler[$class])) {
            return self::$handler[$class];
        }
        return self::$handler[$class] = new $class($options);
    }

    public function header($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->headers[$k] = $v;
            }
        } else {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function write($str = '')
    {
        $this->body .= $str;
        return $this;
    }

    public function status($code = null)
    {
        if ($code === null) {
            return $this->status;
        }
        if (array_key_exists($code, self::$codes)) {
            $this->status = $code;
        } else {
            throw new Exception('Invalid status code');
        }
        return $this;
    }

    public function sendHeaders()
    {
        header(
            sprintf(
                '%s %d %s',
                (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1'),
                $this->status,
                self::$codes[$this->status]
            ),
            true,
            $this->status
        );
        foreach ($this->headers as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header($field . ': ' . $v, false);
                }
            } else {
                header($field . ': ' . $value);
            }
        }
        if (($length = strlen($this->body)) > 0) {
            header('Content-Length: ' . $length);
        }
        return $this;
    }

    public function cache($expires)
    {
        if ($expires === false) {
            $this->headers['Expires']       = 'Mon, 26 Jul 1997 05:00:00 GMT';
            $this->headers['Cache-Control'] = [
                'no-store, no-cache, must-revalidate',
                'post-check=0, pre-check=0',
                'max-age=0',
            ];
            $this->headers['Pragma'] = 'no-cache';
        } else {
            $expires                        = is_int($expires) ? $expires : strtotime($expires);
            $this->headers['Expires']       = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age=' . ($expires - time());
        }
        return $this;
    }

    public function lastModified($time)
    {
        $this->header('Last-Modified', date(DATE_RFC1123, $time));
        if (
            isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $time
        ) {
            $this->halt(304);
        }
    }

    public function redirect($url, $code = 301, $params = [])
    {
        if (!empty($params)) {
            if (is_array($params)) {
                $params = http_build_query($params);
            }
            $url .= ((false !== strpos($url, '?')) ? '&' : '?') . $params;
        }
        $this->status($code)
            ->header('Location', $url)
        // ->write($url)
            ->send();
    }

    public function send($exit = false)
    {
        if (ob_get_length() > 0) {
            $this->body = ob_get_contents() . $this->body;
            ob_end_clean();
        }
        if (!headers_sent()) {
            $this->sendHeaders();
        }
        echo $this->body;
        $exit && exit();
    }

    public function sendHttpStatus($code = 200)
    {
        $this->status($code)
            ->send();
    }

    public function output($message = '', $code = 200)
    {
        $this->status($code)
            ->write($message)
            ->send();
    }

    public function halt($code = 200, $message = '')
    {
        $this->status($code)
            ->write($message)
            ->send(true);
    }

    public function json($data, $code = 200, $encode = true)
    {
        if ($encode) {
            $options = [
                'json_encode_param'  => Config::get('response.json_param'),
                'json_empty_to_null' => Config::get('response.empty_to_null'),
            ];
            $data = JsonResponse::instance($options)->render($data);
        }
        $this->status($code)
            ->header('Content-Type', 'application/json')
            ->write($data)
            ->send();
    }

    public function jsonp($data, $callback = '', $code = 200, $encode = true)
    {
        if ('' == $callback) {
            $var_name = Config::get('var.jsonp_callback');
            $default  = Config::get('response.jsonp_callback', 'callback');
            $callback = Input::get($var_name, $default);
        }
        if ($encode) {
            $options = [
                'json_encode_param'  => Config::get('response.json_param'),
                'json_empty_to_null' => Config::get('response.empty_to_null'),
                'jsonp_handler'      => $callback,
            ];
            $data = JsonpResponse::instance($options)->render($data);
        } else {
            $data = "{$callback}({$data});";
        }
        $this->status($code)
            ->header('Content-Type', 'application/javascript')
            ->write($data)
            ->send();
    }

    public function xml($data = '', $code = 200, $encode = true)
    {
        if ($encode) {
            $data = XmlResponse::instance()->render($data);
        }
        $this->status($code)
            ->header('Content-Type', 'text/xml')
            ->write($data)
            ->send();
    }

    public function html($content = '', $code = 200)
    {
        $this->status($code)
            ->header('Content-Type', 'text/html')
            ->write($content)
            ->send();
    }

    public function log($info = '', $type = 'LOG', $exit = 0)
    {
        $format = "[%s] %s %s\n";
        $now    = date('Y-m-d H:i:s');
        $type   = strtoupper($type);
        $info   = !is_scalar($info) ? var_export($info, 1) : $info;
        echo sprintf($format, $now, $type, $info);
        ob_flush();
        $exit && exit();
    }

    public function clear()
    {
        $this->status  = 200;
        $this->headers = [];
        $this->body    = '';
        return $this;
    }

    protected static function emptyToNull($data)
    {
        if (empty($data)) {
            return null;
        }
        foreach ($data as & $val) {
            if (is_array($val)) {
                $val = self::emptyToNull($val);
            }
        }
        return $data;
    }
}
