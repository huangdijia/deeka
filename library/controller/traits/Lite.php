<?php
namespace deeka\controller\traits;

use deeka\Response;

trait Lite
{
    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception(get_called_class() . "::{$name}() IS NOT EXISTS\n", 1);
    }
}