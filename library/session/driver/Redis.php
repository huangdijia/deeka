<?php
namespace deeka\session\driver;

use deeka\Config;

class Redis
{
    protected $lifeTime    = 3600;
    protected $sessionName = '';
    protected $handler     = null;

    public function open($savePath, $sessName)
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

    public function read($sessID)
    {
        return (string) $this->handler->get($this->sessionName . $sessID);
    }

    public function write($sessID, $sessData)
    {
        return $this->handler->setex($this->sessionName . $sessID, $this->lifeTime, $sessData) ? true : false;
    }

    public function destroy($sessID)
    {
        return $this->handler->del($this->sessionName . $sessID) ? true : false;
    }

    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}
