<?php
namespace deeka;

class App
{
    private static $initialize = [];

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public static function init()
    {
        // 触发钩子
        Hook::trigger('app.init');
        // 记录app_start时间
        Debug::remark('app_start');
        // 设置时区
        date_default_timezone_set(Config::get('app.timezone', 'Asia/Shanghai'));
        // 初始化日志
        Log::init(Config::get('log'));
        // 初始化数据库
        Db::init(Config::get('database'));
        // 设置默认过滤器
        Input::setGlobalFilter(Config::get('default.filter'));
        // CLI模式接收参数
        if (Request::isCli()) {
            // 以url的方式去解析
            $_SERVER['REQUEST_METHOD'] = 'CLI';
            $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
            $_SERVER['REQUEST_URI']    = realpath($_SERVER['SCRIPT_NAME']) . ' ' . (isset($_SERVER['argv'][1]) ? ('"' . $_SERVER['argv'][1] . '"') : '');
            $_SERVER['PATH_INFO']      = '/';
            // cli模式下参数解析
            switch(Config::get('app.cli_params_parse_type')) {
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
            // Session配置
            $sessopts = array_merge(
                (array) Config::get('session.options'),
                [
                    'type'           => Config::get('session.type'),
                    'auto_start'     => Config::get('session.auto_start'),
                    'namespace'      => Config::get('session.namespace'),
                    'var_session_id' => Config::get('var.session_id'),
                ]
            );
            Session::init($sessopts);
        }
        // 路由支持
        self::parse();
        // 获取控制器及操作名
        define('MODULE_NAME', Request::module());
        define('MODULE_PATH', APP_PATH . Str::parseName(MODULE_NAME) . DS);
        define('CONTROLLER_NAME', Request::controller());
        define('CONTROLLER_PATH', MODULE_PATH . 'controller' . DS);
        define('ACTION_NAME', Request::action());
        // 设置目录
        ('' == Config::get('tmpl.tmpl_path')) && Config::set('tmpl.tmpl_path', MODULE_PATH . 'view' . DS);
        ('' == Config::get('tmpl.tmpl_cache_path')) && Config::set('tmpl.tmpl_cache_path', CACHE_PATH);
        // 返回
        return true;
    }

    public static function parse()
    {
        if (
            empty($_SERVER["PATH_INFO"])
            || '/' == $_SERVER["PATH_INFO"]
        ) {
            return;
        }
        $pathinfo = explode('/', trim($_SERVER['PATH_INFO'], '/'));
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

    public static function start()
    {
        // 初始化
        self::init();
        // 触发钩子
        Hook::trigger('app.start');
        // 检查控制器名合法性
        if (!preg_match('/^[A-Za-z](\w)*$/', CONTROLLER_NAME)) {
            $error = "CONTROLLER IS NOT EXISTS: " . CONTROLLER_NAME;
            throw new \Exception($error);
        }
        if (!preg_match('/^[A-Za-z](\w)*$/', ACTION_NAME)) {
            $error = "ACTION IS NOT EXISTS: " . ACTION_NAME;
            throw new \Exception($error);
        }
        // [app.namespace]\[module]\[controller]\[controller_name]
        $controller_name = sprintf(
            "\\%s\\%s\\%s\\%s",
            Config::get('app.namespace', 'app'),
            MODULE_NAME,
            Config::get('layer.controller', 'controller'),
            CONTROLLER_NAME
        );
        $action_name = ACTION_NAME . Config::get('layer.action', '');
        // 加载控制器
        $controller = Loader::controller($controller_name);
        // 加载失败则抛出异常
        if (false === $controller) {
            $error = "CONTROLLER IS NOT EXISTS: '{$controller_name}'";
            throw new \Exception($error, 1);
        }
        // 参数绑定
        try {
            $method = new \ReflectionMethod($controller, $action_name);
            if ($method->isPublic() && !$method->isStatic()) {
                if ($method->getNumberOfParameters() > 0 && Config::get('app.url_params_bind', false)) {
                    // 根据请求方法确定绑定参数
                    switch (Input::server('REQUEST_METHOD', 'GET')) {
                        case 'POST':
                            $vars = array_merge(Input::get(), Input::post());
                            break;
                        case 'PUT':
                            $vars = Input::put();
                            break;
                        default:
                            $vars = Input::get();
                            break;
                    }
                    $params = $method->getParameters();
                    // 参数绑定类型 0 = 变量名, 1 = 顺序
                    $bind_type = Config::get('app.url_params_bind_type', 0);
                    foreach ($params as $param) {
                        $name = $param->getName();
                        if (1 == $bind_type && !empty($vars)) {
                            $args[] = array_shift($vars);
                        } elseif (0 == $bind_type && isset($vars[$name])) {
                            $args[] = $vars[$name];
                        } elseif ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw new \Exception('PARAM ERROR:' . $name);
                        }
                    }
                    $method->invokeArgs($controller, $args);
                } else {
                    $method->invoke($controller);
                }
            } else {
                throw new \ReflectionException();
            }
        } catch (\ReflectionException $e) {
            try {
                $method = new \ReflectionMethod($controller, '__call');
                $method->invokeArgs($controller, [$action_name, '']);
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return;
    }
}
