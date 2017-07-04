<?php
namespace deeka;

Hook::register('app.init', function () {
    // 项目初始化文件，手动加载项目配置及自定义函数
    /**
     * <?php
     * \deeka\Loader::addNamespace([]);
     * \deeka\Loader::addClassMap([]);
     * \deeka\Config::set([]);
     * \deeka\Validate::addRule([]);
     * require_onde APP_PATH . DS . 'functions' . EXT; // 或直接定义方法
     */
    if (is_file(APP_PATH . DS . 'initialize' . EXT)) {
        require_once APP_PATH . DS . 'initialize' . EXT;
    } else {
        // 加载项目命名空间配置
        is_file(APP_PATH . DS . 'namespace' . EXT) && Loader::addNamespace(include APP_PATH . DS . 'namespace' . EXT);
        // 配置项目根命名空间
        Loader::addNamespace(Config::get('app.namespace', 'app'), APP_PATH);
        // 加载钩子
        is_file(APP_PATH . DS . 'hook' . EXT) && require APP_PATH . DS . 'hook' . EXT;
        // 加载别名配置
        is_file(APP_PATH . DS . 'classmap' . EXT) && Loader::addClassMap(include APP_PATH . DS . 'classmap' . EXT);
        // 加载应用配置
        is_file(APP_PATH . DS . 'config' . EXT) && Config::set(include APP_PATH . DS . 'config' . EXT);
        // 加载环境配置
        (!empty(APP_STATUS) && is_file(APP_PATH . DS . APP_STATUS . EXT)) && Config::set(include APP_PATH . DS . APP_STATUS . EXT);
        // 加载应用验证规则
        is_file(APP_PATH . DS . 'validate' . EXT) && Validate::addRule(include APP_PATH . DS . 'validate' . EXT);
        // 加载应用共用方法
        is_file(APP_PATH . DS . 'functions' . EXT) && require_once APP_PATH . DS . 'functions' . EXT;
    }
});

Hook::register('app.dispatch', function() {
    if (Request::isCli()) {
        // 以url的方式去解析
        $_SERVER['REQUEST_METHOD'] = 'CLI';
        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_SERVER['REQUEST_URI']    = realpath($_SERVER['SCRIPT_NAME']) . ' ' . (isset($_SERVER['argv'][1]) ? ('"' . $_SERVER['argv'][1] . '"') : '');
        $_SERVER['PATH_INFO']      = '/';
        // cli模式下参数解析
        switch (Config::get('app.cli_params_parse_type')) {
            // php index.php --c=Controller --a=action
            case 1:
                $_GET = Options::get();
                break;
            // php index.php "c=Controller&a=action"
            case 0:
            default:
                !empty($_SERVER['argv'][1]) && parse_str($_SERVER['argv'][1], $_GET);
                break;
        }
    } else {
        $pathinfo = Input::server('PATH_INFO');
        if (empty($pathinfo) || '/' == $pathinfo) {
            return;
        }
        $pathinfo = explode('/', trim($pathinfo, '/'));
        // 解析路由
        switch (count($pathinfo)) {
            case '0':
                break;
            case '1':
                $_GET[Config::get('var.module')] = $pathinfo[0];
                break;
            case '2':
                $_GET[Config::get('var.module')]     = $pathinfo[0];
                $_GET[Config::get('var.controller')] = $pathinfo[1];
                break;
            case '3':
                $_GET[Config::get('var.module')]     = $pathinfo[0];
                $_GET[Config::get('var.controller')] = $pathinfo[1];
                $_GET[Config::get('var.action')]     = $pathinfo[2];
                break;
            default:
                $_GET[Config::get('var.module')]     = $pathinfo[0];
                $_GET[Config::get('var.controller')] = $pathinfo[1];
                $_GET[Config::get('var.action')]     = $pathinfo[2];
                array_shift($pathinfo);
                array_shift($pathinfo);
                array_shift($pathinfo);
                $keys   = [];
                $values = [];
                foreach ($pathinfo as $index => $value) {
                    if (($index % 2) == 0) {
                        $keys[] = $value;
                    } else {
                        $values[] = $value;
                    }
                }
                $vars = array_combine($keys, $values);
                $_GET = array_merge($_GET, $vars);
                break;
        }
    }
});