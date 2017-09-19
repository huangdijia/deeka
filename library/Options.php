<?php
namespace deeka;

class Options
{
    protected static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public function __call($name, $args)
    {
        return call_user_func_array([self::instance(), $name], $args);
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array([self::instance(), $name], $args);
    }

    private function get(string $name = '', $default = null)
    {
        if ('' == $name) {
            return self::all();
        } else {
            return self::all()[$name] ?? $default;
        }
    }

    private function all()
    {
        static $argv = null;
        if (is_null($argv)) {
            [$shortopts, $longopts] = $this->parse();
            $argv                   = getopt($shortopts, $longopts);
        }
        return $argv;
    }

    private function has($name = '')
    {
        return isset(self::all()[$name]) ? true : false;
    }

    private function parse(): array
    {
        if (empty($_SERVER['argv'])) {
            return $opts = [];
        }
        $opts = ['', []];
        foreach ($_SERVER['argv'] as $argv) {
            if (preg_match('/^\-\-([\w\-]+)/', $argv, $matches)) {
                $opts[1][] = $matches[1] . '::';
            } elseif (preg_match('/^\-([a-z])/', $argv, $matches)) {
                $opts[0] .= $matches[1] . '::';
            }
        }
        return $opts;
    }
}