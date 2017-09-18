<?php
namespace deeka\controller\traits;

use deeka\Input;
use deeka\Log;
use deeka\Reflect;
use deeka\Request;
use deeka\Response;
use Exception;

trait Cli
{
    public function __construct()
    {
        if (!Request::isCli()) {
            Response::instance()->sendHttpStatus(500);
            Log::record("Request invaild!");
            exit;
        }
        // 控制器初始化
        method_exists($this, '_initialize') && Reflect::invokeMethod([$this, '_initialize'], Input::param());
    }

    public function __call($name, $args)
    {
        throw new Exception(get_called_class() . "::{$name}() IS NOT EXISTS\n", 1);
    }

    public function success($info = '')
    {
        Response::instance()->log($info, 'SUCCESS', true);
    }

    public function error($info = '')
    {
        Response::instance()->log($info, 'FAILD', true);
    }

    public function log($info = '')
    {
        Response::instance()->log($info, 'LOG', false);
        ob_flush();
    }
}
