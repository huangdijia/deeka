<?php
namespace deeka\session\driver;

use deeka\Config;
use Predis\Client;
use SessionHandlerInterface;

class Predis implements SessionHandlerInterface
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
        // 集群
        if (Config::get('redis.cluster', false)) {
            $this->handler = new Client(
                Config::get(Config::get('cache.connection', 'redis.clusters.default'), []),
                [
                    'cluster' => Config::get('redis.options.cluster', 'redis'),
                ]
            );
        } else {
            $this->handler = new Client([
                'scheme' => 'tcp',
                'host'   => Config::get('redis.host', '127.0.0.1'),
                'port'   => Config::get('redis.port', '6379'),
            ]);
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
