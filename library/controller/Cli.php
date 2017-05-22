<?php
namespace deeka\controller;

use deeka\Log;
use deeka\Response;

class Cli
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
}
