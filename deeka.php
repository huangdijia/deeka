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
\deeka\Loader::addNamespace(include CORE_PATH . 'namespace.php');
// 导入别名
\deeka\Loader::addClassMap(include CORE_PATH . 'alias.php');
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
\deeka\Config::set(include CORE_PATH . 'config.php');
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
    \deeka\Hook::register('app.init', function () {
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
            \deeka\Loader::addNamespace(\deeka\Config::get('app.namespace', 'app'), APP_PATH);
            // 加载钩子
            is_file(APP_PATH . DS . 'hook' . EXT) && \deeka\Hook::import(include APP_PATH . DS . 'hook' . EXT);
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
