<?php
namespace deeka\controller\traits;

use deeka\Input;
use deeka\Log;
use deeka\Reflect;
use deeka\Response;
use Exception;

trait Ajax
{
    public function __construct()
    {
        //
    }

    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception("Action " . get_called_class() . "::{$name}() is not exists\n", 1);
    }

    protected function jsonReturn($data = null, $callback = '')
    {
        if (isset($data['callback'])) {
            $callback = $data['callback'];
            unset($data['callback']);
        }
        // 返回JSON数据格式到客户端 包含状态信息
        header('Content-Type:application/json; charset=utf-8');
        if ('' != $callback) {
            Response::instance()->jsonp($data, $callback);
        } else {
            Response::instance()->json($data);
        }
        Log::record(preg_replace('/\s+/', ' ', var_export($data, 1)), 'RETURN');
        exit;
    }

    protected function success($info = '', $data = null, $callback = '')
    {
        $_data             = [];
        $_data['status']   = 1;
        $_data['info']     = $info;
        $_data['callback'] = !empty($callback) ? $callback : Input::param('callback', '');
        if (!is_null($data)) {
            $_data['data'] = $data;
        }
        $this->jsonReturn($_data);
    }

    protected function error($info = '', $data = null, $callback = '')
    {
        $_data             = [];
        $_data['status']   = 0;
        $_data['info']     = $info;
        $_data['callback'] = !empty($callback) ? $callback : Input::param('callback', '');
        if (!is_null($data)) {
            $_data['data'] = $data;
        }
        $this->jsonReturn($_data);
    }
}
