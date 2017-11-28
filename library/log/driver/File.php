<?php
namespace deeka\log\driver;

use deeka\Debug;
use deeka\Input;
use deeka\Log;
use Exception;
use Psr\Log\LoggerInterface;

class File extends Log implements LoggerInterface
{
    protected static $config = [
        'on'          => true,
        'type'        => 'File',
        'level'       => 'MERG,ALERT,CRIT,ERR',
        'path'        => '',
        'alone_ip'    => '',
        'time_format' => '[ Y-m-d H:i:s ]',
    ];
    protected static $log = [];

    public function __construct($config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }

    // public function __call($name, $args)
    // {
    //     $name = strtoupper($name);
    //     // check it
    //     if (!in_array($name, array_key(Log::getConstants()))) {
    //         $this->warning('Log::' . strtoupper($name) . ' is not defined');
    //     }
    //     // message is empty, return
    //     if (empty($args[0])) {
    //         return;
    //     }
    //     // record it
    //     $this->record($args[0], $name);
    // }

    public function emergency($message = '', array $contex = [])
    {
        $this->record($message, Log::EMERGENCY);
    }

    public function alert($message = '', array $contex = [])
    {
        $this->record($message, Log::ALERT);
    }

    public function critical($message = '', array $contex = [])
    {
        $this->record($message, Log::CRITICAL);
    }

    public function error($message = '', array $contex = [])
    {
        $this->record($message, Log::ERROR);
    }

    public function warning($message = '', array $contex = [])
    {
        $this->record($message, Log::WARNING);
    }

    public function notice($message = '', array $contex = [])
    {
        $this->record($message, Log::NOTICE);
    }

    public function info($message = '', array $contex = [])
    {
        $this->record($message, Log::INFO);
    }

    public function debug($message = '', array $contex = [])
    {
        $this->record($message, Log::DEBUG);
    }

    public function sql($message = '', array $contex = [])
    {
        $this->record($message, Log::SQL);
    }

    public function log($level, $message = '', array $contex = [])
    {
        $this->record($message, $level);
    }

    // 旧用法
    public function record($message = '', $level = Log::LOG)
    {
        if (!self::$config['on']) {
            return;
        }
        $level     = Log::level($level);
        $log_level = strtoupper(self::$config['level']);
        if ($log_level != 'ALL' && false === strpos($log_level, $level)) {
            return;
        }
        $message     = is_scalar($message) ? $message : var_export($message, 1);
        self::$log[] = sprintf("%s: %s\n", $level, $message);
        return;
    }

    public function save($dest = '')
    {
        if (
            empty(self::$log)
            || !self::$config['on']
        ) {
            return;
        }
        $now  = date(self::$config['time_format']);
        $dest = $this->dest($dest);
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
        } catch (Exception $e) {
            //
        }
        // 清空日志
        self::clear();
    }

    public function write($message = '', $level = Log::LOG, $dest = '')
    {
        if (!is_scalar($message)) {
            $message = var_export($message, 1);
        }
        $now   = date(self::$config['time_format']);
        $level = Log::level($level);
        $dest  = $this->dest($dest);
        try {
            error_log(
                sprintf("%s %s: %s\n\n", $now, $level, $message),
                3,
                $dest
            );
        } catch (Exception $e) {
            //
        }
    }

    protected function dest($dest = '')
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

    public function clear()
    {
        self::$log = [];
    }
}
