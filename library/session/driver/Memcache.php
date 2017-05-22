<?php
namespace deeka\session\driver;

use deeka\Config;

class Memcache
{
    protected $lifeTime    = 3600;
    protected $sessionName = '';
    protected $handler     = null;

    public function open($savePath, $sessName)
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
        $this->handler = new \Memcache;
        foreach ($options['host'] as $i => $host) {
            $port = isset($options['port'][$i]) ? $options['port'][$i] : $options['port'][0];
            $this->handler->addServer($host, $port, $options['persistent'], 1, $options['timeout']);
        }
        return true;
    }

    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handler->close();
        $this->handler = null;
        return true;
    }

    public function read($sessID)
    {
        return (string) $this->handler->get($this->sessionName . $sessID);
    }

    public function write($sessID, $sessData)
    {
        return $this->handler->set($this->sessionName . $sessID, $sessData, 0, $this->lifeTime) ? true : false;
    }

    public function destroy($sessID)
    {
        return $this->handler->delete($this->sessionName . $sessID) ? true : false;
    }

    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}
