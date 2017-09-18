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
    }

    public function __call($name, $args)
    {
        throw new Exception("Action " . get_called_class() . "::{$name}() is not exists\n", 1);
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
