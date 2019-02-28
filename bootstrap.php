<?php
// 定义常量
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('EXT') or define('EXT', '.php');
defined('APP_AUTO_START') or define('APP_AUTO_START', true);
defined('CORE_PATH') or define('CORE_PATH', __DIR__ . DS);
defined('LIB_PATH') or define('LIB_PATH', CORE_PATH . 'library' . DS);
defined('APP_HOME') or define('APP_HOME', realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . DS);
defined('APP_ROOT') or define('APP_ROOT', dirname(APP_HOME) . DS);
defined('APP_PATH') or define('APP_PATH', APP_ROOT . 'app' . DS);
defined('LOG_PATH') or define('LOG_PATH', APP_HOME . 'logs' . DS);
defined('DATA_PATH') or define('DATA_PATH', APP_HOME . 'data' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', APP_HOME . 'cache' . DS);

// 运行信息
define('START_TIME', microtime(true));
define('START_MEM', memory_get_usage());
define('ENV_PREFIX', 'ENV_');

// 版本及版权信息
define('AUTHOR', 'Deeka');
define('VERSION', '2.6');
define('LICENSE', 'MIT');

// ob_start
ob_start();

// 自动加载
// if (!class_exists('\\Composer\\Autoload\\ClassLoader')) {
    require_once LIB_PATH . 'Loader' . EXT;
    // 增加核心类命名空间
    \deeka\Loader::addNamespace(include CORE_PATH . 'namespace.php');
    // 导入别名
    \deeka\Loader::addClassMap(include CORE_PATH . 'alias.php');
    // 注册自动加载
    \deeka\Loader::register();
// }

class_alias('deeka\\Configure', 'deeka\\Config'); // fix error in php7.3

// 注册错误捕获
\deeka\Error::register();

// 环境参数
\deeka\Env::init();

// 项目配置
defined('APP_STATUS') or define('APP_STATUS', \deeka\Env::get('app.status'));
defined('APP_DEBUG') or define('APP_DEBUG', \deeka\Env::get('app.debug'));

// 加载配置
\deeka\Config::set(include CORE_PATH . 'config.php');

// 检测语音
if (\deeka\Config::get('lang.accept')) {
    \deeka\Lang::detect();
    // 加载框架语言包
    foreach (\deeka\Config::get('lang.accept') as $range) {
        is_file(CORE_PATH . $range . EXT) && \deeka\Lang::set(include CORE_PATH . $range . EXT, '', $range);
    }
}

// DEBUG
APP_DEBUG && \deeka\Config::set('log.level', 'all');

// 加载验证规则
// \deeka\Validate::addRule([]);

// 加载助手函数
is_file(CORE_PATH . 'hook' . EXT) && require_once CORE_PATH . 'hook' . EXT;

// 加载钩子
is_file(CORE_PATH . 'helper' . EXT) && require_once CORE_PATH . 'helper' . EXT;

// 默认自动运行模式
APP_AUTO_START && \deeka\App::start();
