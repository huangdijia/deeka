<?php
namespace deeka\controller;

use deeka\Response;

class Lite
{
    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception(get_called_class() . "::{$name}() IS NOT EXISTS\n", 1);
    }
}
