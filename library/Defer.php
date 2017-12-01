<?php
namespace deeka;

class Defer
{
    private $actions         = [];
    private $count           = 0;
    private static $instance = null;

    private function __construct()
    {
        //
    }

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

    private function __clone()
    {
        //
    }

    public function add(\Closure $action)
    {
        $this->actions[] = $action;
        $this->count++;
    }

    public function getCount()
    {
        return $this->count;
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function register(\Closure $action)
    {
        if (!Config::get('defer.on', false)) {
            return;
        }
        self::getInstance()->add($action);
    }

    public static function isDefered()
    {
        return self::getInstance()->getCount() > 0;
    }
}
