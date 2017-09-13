<?php
namespace deeka\controller\traits;

use deeka\Input;
use deeka\Reflect;
use deeka\Response;
use Exception;

trait View
{
    protected $view = null;

    public function __construct(\deeka\View $view)
    {
        // 控制器初始化
        if (method_exists($this, '_initialize')) {
            Reflect::invokeMethod([$this, '_initialize'], Input::param());
        }
        $this->view = $view;
    }

    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception(get_called_class() . "::{$name}() IS NOT EXISTS", 1);
    }

    protected function fetch($templateFile = '')
    {
        if ('' == $templateFile) {
            $templateFile = CONTROLLER_NAME . '/' . ACTION_NAME;
        } elseif (false === strpos($templateFile, '/')) {
            $templateFile = CONTROLLER_NAME . '/' . $templateFile;
        }
        return $this->view->fetch($templateFile);
    }

    protected function render($content = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $this->view->render($content, $charset, $contentType);
    }

    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    protected function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $this->view->display($templateFile, $charset, $contentType);
    }
}
