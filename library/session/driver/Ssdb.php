<?php
namespace deeka\session\driver;

use deeka\Config;
use deeka\Loader;
use deeka\Log;
use deeka\Response;

class Ssdb
{
    protected $lifeTime    = 3600;
    protected $sessionName = '';
    protected $handler     = null;

    public function open($savePath, $sessName)
    {
        Loader::addClassMap('SimpleSSDB', CORE_PATH . '/vendor/ssdb/SSDB.php');
        $options = [
            'host'    => Config::get('ssdb.host', '127.0.0.1'),
            'port'    => Config::get('ssdb.port', '8888'),
            'timeout' => Config::get('session.timeout', 0),
            'expire'  => Config::get('session.expire', 3600),
            'usesn'   => Config::get('session.use_sessname', false),
        ];
        // 有效时间
        $options['expire'] && $this->lifeTime = $options['expire'];
        // 是否使用sessionName
        $options['usesn'] && $this->sessionName = $sessName;
        // 是否设置超时
        try {
            if ($options['timeout'] > 0) {
                $this->handler = new \SimpleSSDB($options['host'], $options['port'], $options['timeout']);
            } else {
                $this->handler = new \SimpleSSDB($options['host'], $options['port']);
            }
        } catch (\SSDBException $e) {
            // 无法捕获异常
            $log = sprintf(
                "%s in %s on line %s\n\nTrack:\n%s\n",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            Log::record($log, Log::ERR);
            $log = nl2br($log);
            @ob_end_clean();
            if (APP_DEBUG) {
                die($log);
                Response::instance()->halt(500, $log);
            } else {
                die($e->getMessage());
                Response::instance()->halt(500, $e->getMessage());
            }
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
        $data = $this->handler->get($this->sessionName . $sessID);
        return unserialize($data);
    }

    public function write($sessID, $sessData)
    {
        return $this->handler->setx($this->sessionName . $sessID, serialize($sessData), $this->lifeTime) ? true : false;
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
