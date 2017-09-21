<?php
namespace deeka;

use Exception;

class Controller
{
    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception(get_called_class() . "::{$name}() does not exists", 1);
    }
}
