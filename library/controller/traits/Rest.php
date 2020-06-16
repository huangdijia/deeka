<?php
namespace deeka\controller\traits;

use deeka\Input;
use deeka\Reflect;
use deeka\Request;
use deeka\Response;
use Exception;

trait Rest
{
    protected $method;
    protected $type;
    protected $allowMethod   = 'get|post|put|delete';
    protected $defaultMethod = 'get';
    protected $allowType     = 'html|xml|json|rss';
    protected $defaultType   = 'html';
    protected $outputType    = [
        'xml'  => 'application/xml',
        'json' => 'application/json',
        'html' => 'text/html',
    ];

    public function __construct()
    {
        $this->type   = $this->defaultType;
        $this->method = $this->defaultMethod;
        if (preg_match("/^{$this->allowType}$/i", Request::ext())) {
            $this->type = Request::ext();
        }
        if (false !== stripos($this->allowMethod, Request::method())) {
            $this->method = Request::method(CASE_LOWER);
        }
    }

    public function __call($method, $args = [])
    {
        if (method_exists($this, $method . '_' . $this->method . '_' . $this->type)) {
            $fun = $method . '_' . $this->method . '_' . $this->type;
        } elseif ($this->method == $this->defaultMethod && method_exists($this, $method . '_' . $this->type)) {
            $fun = $method . '_' . $this->type;
        } elseif ($this->type == $this->defaultType && method_exists($this, $method . '_' . $this->method)) {
            $fun = $method . '_' . $this->method;
        }
        if (isset($fun)) {
            Reflect::invokeMethod([$this, $fun], $args);
        } else {
            // 抛出异常
            throw new Exception("Action " . get_called_class() . "::{$name}() is not exists\n", 1);
        }
    }

    public function response($data, $code = 200)
    {
        switch ($this->type) {
            case 'json':
                Response::instance()->json($data, $code);
                break;
            case 'xml':
                Response::instance()->xml($data, $code);
                break;
            default:
            case 'html':
                Response::instance()->html($data, $code);
                break;
        }
    }
}
