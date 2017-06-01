<?php
namespace deeka\controller\traits;

class View
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
