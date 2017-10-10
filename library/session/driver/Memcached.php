<?php
namespace deeka\session\driver;

use deeka\Config;
use SessionHandlerInterface;

class Memcached implements SessionHandlerInterface
{
    protected $lifeTime    = 3600;
    protected $sessionName = '';
    protected $handler     = null;

    public function open($save_path, $session_name)
    {
        $options = [
            'host'       => explode(',', Config::get('memcache.host')),
            'port'       => explode(',', Config::get('memcache.port')),
            'timeout'    => Config::get('session.timeout', 10),
            'expire'     => Config::get('session.expire', 3600),
            'persistent' => Config::get('memcache.persistent', 0),
            'usesn'      => Config::get('session.use_sessname', false),
        ];
        // 有效时间
        $options['expire'] && $this->lifeTime = $options['expire'];
        // 是否使用sessionName
        $options['usesn'] && $this->sessionName = $sessName;
        // 实例化Memcache
        $this->handler = new \Memcached;
        foreach ($options['host'] as $i => $host) {
            $port = $options['port'][$i] ?? $options['port'][0] ?? '11211';
            $this->handler->addServer($host, $port);
        }
        return true;
    }

    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handler->quit();
        $this->handler = null;
        return true;
    }

    public function read($session_id)
    {
        return (string) $this->handler->get($this->sessionName . $session_id);
    }

    public function write($session_id, $session_data)
    {
        return $this->handler->set($this->sessionName . $session_id, $session_data, $this->lifeTime) ? true : false;
    }

    public function destroy($session_id)
    {
        return $this->handler->delete($this->sessionName . $session_id) ? true : false;
    }

    public function gc($maxlifetime)
    {
        return true;
    }
}
