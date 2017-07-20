<?php
namespace deeka\controller\traits;

use deeka\Log;
use deeka\Request;
use deeka\Response;

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
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }
    }

    public function __call($name, $args)
    {
        throw new \Exception(get_called_class() . "::{$name}() IS NOT EXISTS\n", 1);
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
    }
}
