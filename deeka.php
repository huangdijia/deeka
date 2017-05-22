<?php
// 定义常量
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('EXT') or define('EXT', '.php');
defined('APP_AUTO_START') or define('APP_AUTO_START', true);
defined('CORE_PATH') or define('CORE_PATH', __DIR__ . DS);
defined('LIB_PATH') or define('LIB_PATH', CORE_PATH . 'library' . DS);
defined('APP_ROOT') or define('APP_ROOT', dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . DS);
defined('APP_PATH') or define('APP_PATH', APP_ROOT . 'app' . DS);
defined('APP_HOME') or define('APP_HOME', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('LOG_PATH') or define('LOG_PATH', APP_HOME . 'logs' . DS);
defined('DATA_PATH') or define('DATA_PATH', APP_HOME . 'data' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', APP_HOME . 'cache' . DS);
// 运行信息
define('START_TIME', microtime(true));
define('START_MEM', memory_get_usage());
define('ENV_PREFIX', 'ENV_');
// 版本及版权信息
define('AUTHOR', 'deeka');
define('VERSION', '2.0.0 beta');
define('LICENSE', 'MIT');
// ob_start
ob_start();
// 自动加载
require_once LIB_PATH . 'Loader' . EXT;
// 增加核心类命名空间
\deeka\Loader::addNamespace([
    'deeka' => LIB_PATH,
]);
// 导入别名
\deeka\Loader::addClassMap([
    'deeka\App'                      => LIB_PATH . 'App' . EXT,
    'deeka\Cache'                    => LIB_PATH . 'Cache' . EXT,
    'deeka\cache\File'               => LIB_PATH . 'cache' . DS . 'File' . EXT,
    'deeka\cache\Memcache'           => LIB_PATH . 'cache' . DS . 'Memcache' . EXT,
    'deeka\cache\Ssdb'               => LIB_PATH . 'cache' . DS . 'Ssdb' . EXT,
    'deeka\Config'                   => LIB_PATH . 'Config' . EXT,
    'deeka\Controller'               => LIB_PATH . 'Controller' . EXT,
    'deeka\controller\Ajax'          => LIB_PATH . 'controller' . DS . 'Ajax' . EXT,
    'deeka\controller\Cli'           => LIB_PATH . 'controller' . DS . 'Cli' . EXT,
    'deeka\controller\Lite'          => LIB_PATH . 'controller' . DS . 'Lite' . EXT,
    'deeka\controller\JsonRpc'       => LIB_PATH . 'controller' . DS . 'JsonRpc' . EXT,
    'deeka\Db'                       => LIB_PATH . 'Db' . EXT,
    'deeka\db\Db'                    => LIB_PATH . 'db' . DS . 'Db' . EXT,
    'deeka\db\Mysql'                 => LIB_PATH . 'db' . DS . 'Mysql' . EXT,
    'deeka\Debug'                    => LIB_PATH . 'Debug' . EXT,
    'deeka\Env'                      => LIB_PATH . 'Env' . EXT,
    'deeka\Exception'                => LIB_PATH . 'Exception' . EXT,
    'deeka\exception\ErrorException' => LIB_PATH . 'exception' . DS . 'ErrorException' . EXT,
    'deeka\Input'                    => LIB_PATH . 'Input' . EXT,
    'deeka\Log'                      => LIB_PATH . 'Log' . EXT,
    'deeka\Mysql'                    => LIB_PATH . 'Mysql' . EXT,
    'deeka\Model'                    => LIB_PATH . 'Model' . EXT,
    'deeka\Queue'                    => LIB_PATH . 'Queue' . EXT,
    'deeka\queue\File'               => LIB_PATH . 'queue' . DS . 'File' . EXT,
    'deeka\queue\Ssdb'               => LIB_PATH . 'queue' . DS . 'Ssdb' . EXT,
    'deeka\queue\Redis'              => LIB_PATH . 'queue' . DS . 'Redis' . EXT,
    'deeka\Request'                  => LIB_PATH . 'Request' . EXT,
    'deeka\Response'                 => LIB_PATH . 'Response' . EXT,
    'deeka\response\Xml'             => LIB_PATH . 'response' . DS . 'Xml' . EXT,
    'deeka\response\Json'            => LIB_PATH . 'response' . DS . 'Json' . EXT,
    'deeka\response\Jsonp'           => LIB_PATH . 'response' . DS . 'Jsonp' . EXT,
    'deeka\Security'                 => LIB_PATH . 'Security' . EXT,
    'deeka\Session'                  => LIB_PATH . 'Session' . EXT,
    'deeka\session\Memcache'         => LIB_PATH . 'session' . DS . 'Memcache' . EXT,
    'deeka\session\Ssdb'             => LIB_PATH . 'session' . DS . 'Ssdb' . EXT,
    'deeka\Cookie'                   => LIB_PATH . 'Cookie' . EXT,
    'deeka\Template'                 => LIB_PATH . 'Template' . EXT,
    'deeka\View'                     => LIB_PATH . 'View' . EXT,
    'deeka\Validate'                 => LIB_PATH . 'Validate' . EXT,
]);
// 注册自动加载
\deeka\Loader::register();
// 注册错误捕获
\deeka\Error::register();
// 环境参数
\deeka\Env::init();
// 项目配置
defined('APP_STATUS') or define('APP_STATUS', \deeka\Env::get('app.status'));
defined('APP_DEBUG') or define('APP_DEBUG', \deeka\Env::get('app.debug'));
// 加载配置
\deeka\Config::set([
    // app
    'app'      => [
        // 命名空间
        'namespace'            => 'app',
        // 时区设置
        'timezone'             => 'Asia/Shanghai',
        // 参数绑定
        'url_params_bind'      => true,
        'url_params_bind_type' => 0,
    ],
    // 错误
    'error'    => [
        'code' => 500,
    ],
    // 参数名
    'var'      => [
        'action'         => 'a',
        'controller'     => 'c',
        'csrf'           => '__csrf__',
        'jsonp_callback' => 'callback',
        'module'         => 'm',
        'session_id'     => '',
    ],
    // 默认参数
    'default'  => [
        // 模块
        'module'     => 'index',
        // 控制器
        'controller' => 'Index',
        // 操作
        'action'     => 'index',
        // 过滤器
        'filter'     => '',
    ],
    // 层级命名
    'layer'    => [
        'action'     => '',
        'controller' => 'controller',
    ],
    // 会话
    'session'  => [
        'auto_start'   => false,
        'type'         => '',
        'namespace'    => '',
        'expire'       => 3600,
        'timeout'      => 0,
        'use_sessname' => false,
        'options'      => [],
    ],
    // csrf
    'csrf'     => [
        'on' => false,
    ],
    // 日志
    'log'      => [
        'on'          => true,
        'type'        => 'File',
        'level'       => 'MERG,ALERT,CRIT,ERR',
        'alone_ip'    => '',
        'path'        => LOG_PATH,
        'time_format' => '[ Y-m-d H:i:s ]',
    ],
    // 缓存
    'cache'    => [
        'file' => [
            'path'    => CACHE_PATH,
            'type'    => 'File',
            'check'   => false,
            'prefix'  => 'cache_',
            'expire'  => 120,
            'timeout' => 0,
        ]
    ],
    // 队列
    'queue'    => [
        'file' => [
            'path'   => DATA_PATH,
            'prefix' => 'queue_',
            'type'   => 'File',
        ]
    ],
    // 模板配置
    'tmpl'     => [
        'layout_on'           => false,
        'layout_name'         => 'layout',
        'layout_item'         => '{__CONTENT__}',
        'tmpl_cache_on'       => true,
        'tmpl_cache_time'     => 0,
        'tmpl_cache_path'     => '',
        'tmpl_cache_suffix'   => EXT,
        'tmpl_charset'        => 'utf-8',
        'tmpl_strip_space'    => true,
        'tmpl_deny_func_list' => 'phpinfo,exec',
        'tmpl_engine'         => '',
        'tmpl_suffix'         => '.html',
        'tmpl_glue'           => DS,
        'tmpl_path'           => '',
        'tmpl_parse_string'   => [],
        'tmpl_output_charset' => 'utf-8',
        'tmpl_var_identify'   => 'array',
    ],
    // response
    'response' => [
        'json_param'     => JSON_UNESCAPED_UNICODE,
        'jsonp_callback' => 'callback',
    ],
    // ssdb
    'ssdb'     => [
        'host' => '127.0.0.1',
        'port' => 8888,
    ],
    // redis
    'redis'    => [
        'host' => '127.0.0.1',
        'port' => 8888,
    ],
    // memcache
    'memcache' => [
        'host'       => '127.0.0.1',
        'port'       => 11211,
        'persistent' => false,
    ],
    // 数据库配置
    'database' => [],
]);
// DEBUG
if (APP_DEBUG) {
    \deeka\Config::set('log.level', 'all');
}
// 加载验证规则
\deeka\Validate::addRule([]);
// 加载助手函数
is_file(CORE_PATH . 'helper' . EXT) && require_once CORE_PATH . 'helper' . EXT;
// 默认自动运行模式
if (defined('APP_AUTO_START') && APP_AUTO_START) {
    \deeka\App::init(function () {
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
            is_file(APP_PATH . DS . 'namespace' . EXT) && \deeka\Loader::addNamespace(include APP_PATH . DS . 'namespace' . EXT);
            // 配置项目根命名空间
            \deeka\Loader::addNamespace([
                \deeka\Config::get('app.namespace', 'app') => APP_PATH,
            ]);
            // 加载别名配置
            is_file(APP_PATH . DS . 'classmap' . EXT) && \deeka\Loader::addClassMap(include APP_PATH . DS . 'classmap' . EXT);
            // 加载应用配置
            is_file(APP_PATH . DS . 'config' . EXT) && \deeka\Config::set(include APP_PATH . DS . 'config' . EXT);
            // 加载环境配置
            (!empty(APP_STATUS) && is_file(APP_PATH . DS . APP_STATUS . EXT)) && \deeka\Config::set(include APP_PATH . DS . APP_STATUS . EXT);
            // 加载应用验证规则
            is_file(APP_PATH . DS . 'validate' . EXT) && \deeka\Validate::addRule(include APP_PATH . DS . 'validate' . EXT);
            // 加载应用共用方法
            is_file(APP_PATH . DS . 'functions' . EXT) && require_once APP_PATH . DS . 'functions' . EXT;
        }
    });
    // 运行项目
    \deeka\App::start();
}
