<?php

namespace deeka\log\driver;

use RuntimeException;
use deeka\log\LoggerInterface;

class File implements LoggerInterface
{
    protected $dataFormat = 'Y-m-d H:i:s';
    protected $logFormat  = "[%s] %s %s\n";
    protected $logPath;

    public function __construct(array $config = [])
    {
        $this->logPath = $config['file']['path'] ?? LOG_PATH;
    }

    /**
     * 记录日志
     * @param string $message
     * @param array $context
     * @param string $dest
     * @return void
     * @throws Exception
     */
    public function info(string $message = '', array $context = null, string $dest = '')
    {
        // 检测目录是否可以写
        if (!is_writable($this->logPath)) {
            throw new RuntimeException("日誌目錄 {$this->logPath} 不可寫", 1);
        }

        error_log($this->formatMessage($message, $context), 3, $this->getLogFile($dest));
    }

    /**
     * 格式化内容
     * @param string $message
     * @param array|null $context
     * @return string
     */
    protected function formatMessage($message = '', $context = null)
    {
        return sprintf(
            $this->logFormat,
            date($this->dataFormat),
            $message,
            $context ? json_encode($context) : ''
        );
    }

    /**
     * 获取日志文件
     * @param string $dest
     * @return string
     * @throws Exception
     */
    protected function getLogFile($dest = '')
    {
        /// 格式化 $dest
        $dest = $this->parseDest($dest);

        return sprintf(
            '%s/%s%s.log',
            rtrim($this->logPath, '/'),
            $dest ? ($dest . '_') : '',
            date('ymd')
        );
    }

    /**
     * 解析日志路径
     * @param string $dest
     * @return string
     * @throws Exception
     */
    protected function parseDest($dest = '')
    {
        if ($dest == '') {
            return $dest;
        }

        // 去掉空格
        $dest = trim($dest);

        // 兼容全路径
        $dest = str_replace('//', '/', $dest);
        $dest = str_replace($this->logPath, '', $dest);

        // 去掉后缀
        $dest = str_replace(array('.html', '.htm', '.log'), '', $dest);

        // 去掉两头的 /
        $dest = trim($dest, '/');

        // 重新封装 $dest
        if (false === strpos($dest, '/')) {
            return $dest;
        }

        $module     = strstr($dest, '/', true);
        $modulePath = $this->logPath . '/' . $module;

        // 创建模块目录
        if (!is_dir($modulePath) && !mkdir($modulePath)) {
            throw new RuntimeException("{$modulePath} 创建失败", 1);
        }

        // 替换 $action 中的 /
        $action = strstr($dest, '/');
        $action = trim($action);
        $action = trim($action, '/');
        $action = str_replace('/', '_', $action);

        // 重新拼接 $dest
        $dest = $module . '/' . $action;

        return $dest;
    }
}
