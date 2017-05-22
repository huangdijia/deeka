<?php
namespace deeka;

class Controller
{
    protected $_view = null;

    protected function initView()
    {
        if (is_null($this->_view)) {
            $this->_view = View::instance(Config::get('tmpl'));
        }
    }

    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception(get_called_class() . "::{$name}() IS NOT EXISTS", 1);
    }

    protected function jsonReturn($data = null, $callback = null)
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
        Log::record(var_export($data, 1), 'RETURN');
        exit;
    }

    protected function success($info = '', $data = null)
    {
        $_data['status'] = 1;
        $_data['info']   = $info;
        if (!is_null($data)) {
            $_data['data'] = $data;
        }
        $this->jsonReturn($_data);
    }

    protected function error($info = '', $data = null)
    {
        $_data['status'] = 0;
        $_data['info']   = $info;
        if (!is_null($data)) {
            $_data['data'] = $data;
        }
        $this->jsonReturn($_data);
    }

    protected function fetch($templateFile = '')
    {
        $this->initView();
        if ('' == $templateFile) {
            $templateFile = CONTROLLER_NAME . '/' . ACTION_NAME;
        } elseif (false === strpos($templateFile, '/')) {
            $templateFile = CONTROLLER_NAME . '/' . $templateFile;
        }
        return $this->_view->fetch($templateFile);
    }

    protected function render($content = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $this->initView();
        $this->_view->render($content, $charset, $contentType);
    }

    protected function assign($name, $value = '')
    {
        $this->initView();
        $this->_view->assign($name, $value);
    }

    protected function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $this->initView();
        $this->_view->display($templateFile, $charset, $contentType);
    }
}
