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
        $this->view = $view;
    }

    public function __call($name, $args)
    {
        Response::instance()->sendHttpStatus(404);
        throw new Exception("Action " . get_called_class() . "::{$name}() is not exists", 1);
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
