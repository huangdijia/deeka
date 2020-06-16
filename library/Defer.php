<?php
namespace deeka;

use Closure;
use deeka\traits\Singleton;
use deeka\traits\SingletonInstance;

class Defer
{
    use Singleton;
    use SingletonInstance;

    private $actions = [];
    private $count   = 0;

    public function __destruct()
    {
        if (empty($this->actions)) {
            return;
        }
        $this->actions = array_reverse($this->actions);
        foreach ($this->actions as $action) {
            call_user_func_array($action, []);
        }
        Log::save();
    }

    /**
     * Add
     * @param Closure $action 
     * @return void 
     */
    public function add(\Closure $action)
    {
        $this->actions[] = $action;
        $this->count++;
    }

    /**
     * Get count
     * @return int 
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Register
     * @param Closure $action 
     * @return void 
     */
    public static function register(\Closure $action)
    {
        if (!Config::get('defer.on', false)) {
            return;
        }

        self::getInstance()->add($action);
    }

    /**
     * Is defered
     * @return bool 
     */
    public static function isDefered()
    {
        return self::getInstance()->getCount() > 0;
    }
}
