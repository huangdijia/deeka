<?php
namespace deeka\log;

use deeka\Debug;
use deeka\Input;
use deeka\Log;
use Exception;
use RuntimeException;

class Manager
{
    protected $config = [
        'on'          => true,
        'level'       => 'MERG,ALERT,CRIT,ERR',
        'alone_ip'    => '',
        'time_format' => '[ Y-m-d H:i:s ]',
        'channels'    => ['file'],
        'file'        => [
            'path' => LOG_PATH,
        ],
        'papertrail'  => [
            'host'  => '127.0.0.1',
            'port'  => 1111,
            'ident' => 'web',
        ],
    ];
    protected $log      = [];
    protected $channels = [];

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    public function emergency($message = '', array $context = [])
    {
        $this->record($message, Log::EMERGENCY, $context);
    }

    public function alert($message = '', array $context = [])
    {
        $this->record($message, Log::ALERT, $context);
    }

    public function critical($message = '', array $context = [])
    {
        $this->record($message, Log::CRITICAL, $context);
    }

    public function error($message = '', array $context = [])
    {
        $this->record($message, Log::ERROR, $context);
    }

    public function warning($message = '', array $context = [])
    {
        $this->record($message, Log::WARNING, $context);
    }

    public function notice($message = '', array $context = [])
    {
        $this->record($message, Log::NOTICE, $context);
    }

    public function info($message = '', array $context = [])
    {
        $this->record($message, Log::INFO, $context);
    }

    public function debug($message = '', array $context = [])
    {
        $this->record($message, Log::DEBUG, $context);
    }

    public function sql($message = '', array $context = [])
    {
        $this->record($message, Log::SQL, $context);
    }

    public function log($message = '', array $context = [])
    {
        $this->record($message, Log::LOG, $context);
    }

    /**
     * 记录
     * @param string $message
     * @param string $level
     * @return void
     */
    public function record($message = '', $level = Log::LOG, $context = [])
    {
        if (!$this->config['on']) {
            return;
        }

        $level    = Log::level($level);
        $logLevel = $this->config['level'];

        // 任意类型
        if (is_scalar($logLevel) && in_array(strtoupper($logLevel), ['ANY', 'ALL', ''])) {
            $logLevel = [$level];
        } else {
            if (is_scalar($logLevel)) {
                $logLevel = explode(',', $logLevel);
            }

            // 强转类型
            if (is_object($logLevel)) {
                $logLevel = (array) $logLevel;
            }

            // 转大写
            $logLevel = array_map('strtoupper', $logLevel);
        }

        // 判断是否记录
        if (false === in_array($level, $logLevel)) {
            return;
        }

        if (!is_scalar($message)) {
            $message = var_export($message, 1);
        }

        if ($context) {
            $message .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $this->log[] = sprintf("%s: %s\n", $level, $message);

        return;
    }

    /**
     * 保存
     * @param string $dest
     * @return void
     */
    public function save($dest = '')
    {
        if (empty($this->log) || !$this->config['on']) {
            return;
        }

        $now  = date($this->config['time_format'] ?? '[ Y-m-d H:i:s ]');
        $dest = $this->dest($dest);

        // 统计执行时间
        Debug::remark('app_end');
        $runtime = '[' . Debug::getRangeTime('app_start', 'app_end') . 'sec]';

        // 记录日志
        try {
            $message = sprintf(
                "%s %s %s %s %s\n%s",
                $now,
                Input::server('REMOTE_ADDR'),
                Input::server('REQUEST_METHOD'),
                Input::server('REQUEST_URI'),
                $runtime,
                join('', $this->log)
            );

            return $this->send($message, [], $dest);
        } finally {
            // 清空日志
            $this->clear();
        }
    }

    /**
     * 直写
     * @param string $message
     * @param string $level
     * @param string $dest
     * @return void
     */
    public function write($message = '', $level = Log::LOG, $dest = '')
    {
        if (!is_scalar($message)) {
            $message = var_export($message, 1);
        }

        $now     = date($this->config['time_format'] ?? '[ Y-m-d H:i:s ]');
        $level   = Log::level($level);
        $dest    = $this->dest($dest);
        $message = sprintf("%s %s: %s", $now, $level, $message);

        return $this->send($message, [], $dest);
    }

    /**
     * 解析 dest
     * @param string $dest
     * @return mixed
     */
    protected function dest($dest = '')
    {
        if (!empty($dest)) {
            return $dest;
        }

        if ('' != $this->config['alone_ip'] && false !== strpos(Input::server('REMOTE_ADDR'), $this->config['alone_ip'])) {
            return Input::server('REMOTE_ADDR');
        }

        return $dest;
    }

    /**
     * 清空 log
     * @return void
     */
    public function clear()
    {
        $this->log = [];
    }

    /**
     * 创建驱动
     * @param string $name
     * @return \deeka\log\LoggerInterface
     * @throws RuntimeException
     */
    protected function createDriver($name = 'file')
    {
        if (!isset($this->channels[$name])) {
            $driver = '\\deeka\\log\\driver\\' . ucfirst($name);

            if (!class_exists($driver)) {
                throw new RuntimeException("Log driver '{$driver}' is not exists!");
            }

            $this->channels[$name] = new $driver($this->config);
        }

        return $this->channels[$name];
    }

    /**
     * 发送
     * @param string $message
     * @param array $context
     * @param string $dest
     * @return bool
     * @throws RuntimeException
     */
    protected function send($message = '', $context = [], $dest = '')
    {
        foreach ($this->config['channels'] as $channel) {
            $this->createDriver($channel)->info($message, $context, $dest);
        }

        return true;
    }
}
