<?php
namespace deeka;

use Exception;
use ReflectionException;

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
            throw new Exception("Controller does not exists:" . CONTROLLER_NAME, 1);
        }
        if (!preg_match('/^[A-Za-z](\w)*$/', ACTION_NAME)) {
            throw new Exception("Action does not exists:" . ACTION_NAME, 1);
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
        // 加载失败则抛出异常
        if (!class_exists($controller_name)) {
            throw new Exception("Controller {$controller_name} does not exists", 1);
        }
        // 参数绑定类型
        $bind_type = Config::get('app.url_params_bind_type', 0);
        // 加载控制器
        try {
            $controller = Reflect::invokeClass($controller_name, Input::param(), $bind_type);
        } catch (ReflectionException | Exception $e) {
            throw $e;
        }
        // 参数绑定, 参数绑定类型 0 = 变量名, 1 = 顺序
        try {
            if (method_exists($controller, $action_name)) {
                Reflect::invokeMethod([$controller, $action_name], Input::param(), $bind_type);
            } elseif (method_exists($controller, '__call')) {
                Reflect::invokeMethod([$controller, '__call'], [$action_name, Input::param()], 1);
            } else {
                throw new Exception("Action {$controller_name}::{$action_name}() is not exists", 1);
            }
        } catch (ReflectionException | Exception $e) {
            throw $e;
        }
        return;
    }
}
