<?php
namespace deeka;

class Log
{
    const EMERG  = 'EMERG';
    const ALERT  = 'ALERT';
    const CRIT   = 'CRIT';
    const ERR    = 'ERR';
    const WARN   = 'WARN';
    const NOTICE = 'NOTICE';
    const INFO   = 'INFO';
    const DEBUG  = 'DEBUG';
    const SQL    = 'SQL';
    const LOG    = 'LOG';
    /**
     * @var array 配置
     */
    protected static $config = [
        'on'          => true,
        'type'        => 'File',
        'level'       => 'MERG,ALERT,CRIT,ERR',
        'path'        => '',
        'alone_ip'    => '',
        'time_format' => '[ Y-m-d H:i:s ]',
    ];
    /**
     * @var array 映射
     */
    protected static $map = [
        1     => 'ERR', // E_ERROR
        2     => 'WARN', // E_WARNING
        4     => 'ERR', // E_PARSE
        8     => 'NOTICE', // E_NOTICE
        16    => 'ERR', // E_CORE_ERROR
        32    => 'WARN', // E_CORE_WARNING
        64    => 'ERR', // E_COMPILE_ERROR
        128   => 'WARN', // E_COMPILE_WARNING
        256   => 'ERR', // E_USER_ERROR
        512   => 'WARN', // E_USER_WARNING
        1024  => 'NOTICE', // E_USER_NOTICE
        2048  => 'ERR', // E_STRICT
        4096  => 'ERR', // E_RECOVERABLE_ERROR
        32767 => 'INFO', // E_ALL
    ];
    /**
     * @var array 日志集
     */
    protected static $log = [];

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    /**
     * @param $name 方法名
     * @param $args 参数
     * @return null
     */
    public static function __callStatic($name, $args)
    {
        $name = strtolower($name);
        if ($name == 'error') {
            $name = 'err';
        }
        if (!in_array($name, ['emerg', 'alert', 'crit', 'err', 'warn', 'info', 'debug', 'sql', 'log'])) {
            throw new \Exception(__CLASS__ . '::' . strtoupper($name) . ' is not defined', 1);
        }
        if (empty($args[0])) {
            return;
        }
        Log::record($args[0], strtoupper($name));
    }

    /**
     * @param array $config 配置参数
     */
    public static function init(array $config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * @param $message 日志内容
     * @param $level 级别
     * @return null
     */
    public static function record($message = '', $level = self::LOG)
    {
        if (!self::$config['on']) {
            return;
        }
        $level     = self::level($level);
        $log_level = strtoupper(self::$config['level']);
        if ($log_level != 'ALL' && false === strpos($log_level, $level)) {
            return;
        }
        $message     = is_scalar($message) ? $message : var_export($message, 1);
        self::$log[] = sprintf("%s: %s\n", $level, $message);
        return;
    }

    /**
     * @param $dest 保存位置
     * @return null
     */
    public static function save($dest = '')
    {
        if (
            empty(self::$log)
            || !self::$config['on']
        ) {
            return;
        }
        $now  = date(self::$config['time_format']);
        $dest = self::dest($dest);
        // 统计执行时间
        Debug::remark('app_end');
        $runtime = '[' . Debug::getRangeTime('app_start', 'app_end') . 'sec]';
        // 记录日志
        try {
            error_log(
                sprintf(
                    "%s %s %s %s %s\n%s\n",
                    $now,
                    Input::server('REMOTE_ADDR'),
                    Input::server('REQUEST_METHOD'),
                    Input::server('REQUEST_URI'),
                    $runtime,
                    join('', self::$log)
                ),
                3,
                $dest
            );
        } catch (\Exception $e) {
            //
        }
        // 清空日志
        self::clear();
    }

    /**
     * @param $message 日志内容
     * @param $level 日志级别
     * @param null
     */
    public static function write($message = '', $level = self::LOG, $dest = '')
    {
        if (!is_scalar($message)) {
            $message = var_export($message, 1);
        }
        $now   = date(self::$config['time_format']);
        $level = self::level($level);
        $dest  = self::dest($dest);
        try {
            error_log(
                sprintf("%s %s: %s\n\n", $now, $level, $message),
                3,
                $dest
            );
        } catch (\Exception $e) {
            //
        }
    }

    /**
     * @param $dest 保存位置
     * @return mixed
     */
    protected static function dest($dest = '')
    {
        if (!empty($dest)) {
            return $dest;
        }
        if (
            '' != self::$config['alone_ip']
            && false !== strpos(Input::server('REMOTE_ADDR'), self::$config['alone_ip'])
        ) {
            $dest = sprintf('%s/%s_%s.log', self::$config['path'], Input::server('REMOTE_ADDR'), date('y_m_d'));
        } else {
            $dest = sprintf('%s/%s.log', self::$config['path'], date('y_m_d'));
        }
        return $dest;
    }

    /**
     * @param $level 级别
     */
    protected static function level($level = '')
    {
        if (is_numeric($level) && isset(self::$map[$level])) {
            return self::$map[$level];
        }
        return strtoupper($level);
    }

    /**
     * 清空
     */
    public static function clear()
    {
        self::$log = [];
    }
}
