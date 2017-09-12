<?php
namespace deeka;

class App
{
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
        if (!Request::isCli()) {
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
        // 执行钩子
        Hook::trigger('app.dispatch');
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

    public static function start()
    {
        // 初始化
        self::init();
        // 触发钩子
        Hook::trigger('app.start');
        // 检查控制器名合法性
        if (!preg_match('/^[A-Za-z](\w)*$/', CONTROLLER_NAME)) {
            $error = "Controller does not exists:" . CONTROLLER_NAME;
            throw new \Exception($error);
        }
        if (!preg_match('/^[A-Za-z](\w)*$/', ACTION_NAME)) {
            $error = "Action does not exists:" . ACTION_NAME;
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
            $error = "Controller does not exists: '{$controller_name}'";
            throw new \Exception($error, 1);
        }
        // 参数绑定
        try {
            // 参数绑定类型 0 = 变量名, 1 = 顺序
            self::invokeMethod([$controller, $action_name], Input::param(), Config::get('app.url_params_bind_type', 0));
        } catch (\ReflectionException $e) {
            try {
                self::invokeMethod([$controller, '__call'], [$action_name, Input::param()], 1);
            } catch (\ReflectionException $e) {
                throw new \Exception("Error action:{$action_name}");
            }
        }
        return;
    }

    /**
     * @param $method 发射方法
     * @param array $vars 参数
     * @param $bind_type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function invokeMethod($method, $vars = [], $bind_type = 0)
    {
        if (is_array($method)) {
            $object  = is_object($method[0]) ? $method[0] : new $method[0]();
            $reflect = new \ReflectionMethod($object, $method[1]);
        } else {
            $reflect = new \ReflectionMethod($method);
        }
        $args = self::bindParams($reflect, $vars, $bind_type);
        return $reflect->invokeArgs($object ?? null, $args);
    }

    /**
     * @param $reflect 反射对象
     * @param array $vars 参数
     * @param $bind_type 参数绑定类型 0 = 变量名, 1 = 顺序
     * @return mixed
     */
    public static function bindParams($reflect, $vars = [], $bind_type = 0)
    {
        $vars = $vars ?? Input::param();
        $args = [];
        if ($reflect->getNumberOfParameters() > 0) {
            // 判断数组类型 数字数组时按顺序绑定参数
            $params = $reflect->getParameters();
            foreach ($params as $param) {
                $name  = $param->getName();
                $class = $param->getClass();
                if ($class) {
                    $cn     = $class->getName();
                    $args[] = method_exists($cn, 'instance') ? $cn::instance() : new $cn;
                } elseif (1 == $bind_type && !empty($vars)) {
                    $args[] = array_shift($vars);
                } elseif (0 == $bind_type && isset($vars[$name])) {
                    $args[] = $vars[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new \Exception('Error param:' . $name);
                }
            }
        }
        return $args;
    }
}
