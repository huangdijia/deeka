<?php
namespace deeka;

class Controller
{
    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception(get_called_class() . "::{$name}() IS NOT EXISTS", 1);
    }
}
