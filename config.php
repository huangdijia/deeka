<?php return [
    // app
    'app'      => [
        // 命名空间
        'namespace'             => 'app',
        // 时区设置
        'timezone'              => 'Asia/Shanghai',
        // 参数绑定
        'url_params_bind'       => true,
        'url_params_bind_type'  => 0,
        'cli_params_parse_type' => 0,
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
        // 语言
        'lang'       => 'zh-cn',
    ],
    'lang'     => [
        'accept'     => [],
        'cookie_var' => 'lang',
        'detect_var' => 'lang',
    ],
    // 层级命名
    'layer'    => [
        'action'     => '',
        'controller' => 'controller',
    ],
    // 会话
    'session'  => [
        'auto_start'   => false,
        'connection'   => null, // redis.clusters.default
        'type'         => '', // memcache,memcached,redis,predis,ssdb
        'namespace'    => '',
        'expire'       => 3600,
        'timeout'      => 0,
        'use_sessname' => false,
        'options'      => [],
    ],
    // defer
    'defer'    => [
        'on' => false,
    ],
    // csrf
    'csrf'     => [
        'on' => false,
    ],
    // 日志
    'log'      => [
        'on'          => true,
        'level'       => 'EMERGENCY,ALERT,CRITICAL,ERROR',
        'alone_ip'    => '',
        'time_format' => '[ Y-m-d H:i:s ]',
        'channels'    => ['file'],
        'file'        => [
            'path' => rtrim(LOG_PATH, '/'),
        ],
        'papertrail'  => [
            'host'  => '127.0.0.1',
            'port'  => 1111,
            'ident' => 'web',
        ],
    ],
    // 缓存
    'cache'    => [
        'type'       => 'File', // file,memcache,memcached,redis,predis,ssdb
        'connection' => null,
        'path'       => CACHE_PATH,
        'check'      => false,
        'prefix'     => 'cache_',
        'expire'     => 120,
        'timeout'    => 0,
    ],
    // 队列
    'queue'    => [
        'type'   => 'File',
        'path'   => DATA_PATH,
        'prefix' => 'queue_',
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
        'tmpl_strip_space'    => false,
        'tmpl_deny_func_list' => 'phpinfo,exec',
        'tmpl_engine'         => '',
        'tmpl_suffix'         => '.html',
        'tmpl_glue'           => DS,
        'tmpl_path'           => '',
        'tmpl_parse_string'   => [],
        'tmpl_output_charset' => 'utf-8',
        'tmpl_var_identify'   => '', // array, object
    ],
    // response
    'response' => [
        'json_param'     => JSON_UNESCAPED_UNICODE,
        'jsonp_callback' => 'callback',
        'empty_to_null'  => false,
    ],
    // ssdb
    'ssdb'     => [
        'host' => '127.0.0.1',
        'port' => 8888,
    ],
    // redis
    'redis'    => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'cluster'  => false,
        'options'  => [
            'cluster' => 'redis',
        ],
        'clusters' => [
            'default' => [
                // 'tcp://127.0.0.1:7000',
                // 'tcp://127.0.0.1:7001',
                // 'tcp://127.0.0.1:7002',
            ],
        ],
    ],
    // memcache
    'memcache' => [
        'host'       => '127.0.0.1',
        'port'       => 11211,
        'persistent' => false,
    ],
    // 数据库配置
    'database' => [],
];
