<?php
namespace deeka\session\driver;

use deeka\Config;
use SessionHandlerInterface;

class Redis implements SessionHandlerInterface
{
    protected $lifeTime    = 3600;
    protected $sessionName = '';
    protected $handler     = null;

    public function open($save_path, $session_name)
    {
        $options = [
            'host'       => explode(',', Config::get('redis.host')),
            'port'       => explode(',', Config::get('redis.port')),
            'timeout'    => Config::get('session.timeout', 10),
            'expire'     => Config::get('session.expire', 3600),
            'persistent' => Config::get('redis.persistent', 0),
            'usesn'      => Config::get('session.use_sessname', false),
        ];

        // 有效时间
        $options['expire'] && $this->lifeTime = $options['expire'];

        // 是否使用sessionName
        $options['usesn'] && $this->sessionName = $sessName;

        // 实例化Memcache
        $this->handler = new \Redis;

        if ($options['persistent']) {
            $this->handler->pconnect($options['host'], $options['port']);
        } else {
            $this->handler->connect($options['host'], $options['port']);
        }

        return true;
    }

    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        // $this->handler->close();
        $this->handler = null;

        return true;
    }

    public function read($session_id)
    {
        return (string) $this->handler->get($this->sessionName . $session_id);
    }

    public function write($session_id, $session_data)
    {
        return $this->handler->setex($this->sessionName . $session_id, $this->lifeTime, $session_data) ? true : false;
    }

    public function destroy($session_id)
    {
        return $this->handler->del($this->sessionName . $session_id) ? true : false;
    }

    public function gc($maxlifetime)
    {
        return true;
    }
}
