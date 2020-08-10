<?php

namespace deeka\log\driver;

use deeka\log\LoggerInterface;
use RuntimeException;

class Papertrail implements LoggerInterface
{
    protected $ip;
    protected $port;
    protected $dataFormat = 'Y-m-d H:i:s';
    protected $logFormat  = "[%s] %s %s\n";
    protected $ident;
    protected $sock;

    public function __construct(array $config = [])
    {
        $this->ip     = $config['papertrail']['host'] ?? '127.0.0.1';
        $this->port   = $config['papertrail']['port'] ?? 1111;
        $this->ident  = $config['papertrail']['ident'] ?? '';
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    /**
     * 记录日志
     * @param string $message
     * @param array|null $context
     * @param string $dest
     * @return void
     */
    public function info(string $message = '', ?array $context = null, string $dest = '')
    {
        $ident = trim($this->ident . str_replace('/', '_', $dest), '_');

        $this->write($this->formatMessage($message, $context), $ident);
    }

    /**
     * 发送日志
     * @param string $message
     * @param string $component
     * @param string $program
     * @return void
     */
    protected function write($message = '', $ident = "web")
    {
        $header = $this->makeCommonSyslogHeader($ident);
        $lines  = $this->splitMessageIntoLines($message);

        foreach ($lines as $line) {
            $this->send($this->assembleMessage($line, $header));
        }
    }

    /**
     * 发送消息
     * @param string $chunk
     * @return void
     * @throws RuntimeException
     */
    protected function send($chunk = '')
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('The UdpSocket to ' . $this->ip . ':' . $this->port . ' has been closed and can not be written to anymore');
        }

        socket_sendto($this->socket, $chunk, strlen($chunk), $flags = 0, $this->ip, $this->port);
    }

    /**
     * 组装消息
     * @param string $message
     * @param string $header
     * @return string
     */
    protected function assembleMessage($message = '', $header = '')
    {
        return $header . $message;
    }

    /**
     * 封装消息头
     * @param string $ident
     * @return string
     */
    protected function makeCommonSyslogHeader($ident = 'web')
    {
        $priority = 22;

        if (!$pid = getmypid()) {
            $pid = '-';
        }

        if (!$hostname = gethostname()) {
            $hostname = '-';
        }

        $date = date('M d H:i:s');

        return "<$priority>"
            . $date
            . " "
            . $hostname
            . " "
            . $ident
            . "["
            . $pid
            . "]: ";
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
     * 切割消息
     * @param mixed $message
     * @return array|false
     */
    private function splitMessageIntoLines($message)
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }

        return preg_split('/$\R?^/m', (string) $message, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 关闭连接
     * @return void
     */
    public function close()
    {
        if (is_resource($this->socket)) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }
}
