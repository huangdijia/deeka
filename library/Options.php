<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;

class Options
{
    use Singleton;
    use SingletonCallable;
    use SingletonInstance;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    private function get(string $name = '', $default = null)
    {
        if ('' == $name) {
            return self::all();
        } else {
            return self::all()[$name] ?? $default;
        }
    }

    private function all(): array
    {
        static $argv = null;
        if (is_null($argv)) {
            list($shortopts, $longopts) = $this->parse();
            $argv                       = getopt($shortopts, $longopts);
        }
        return $argv;
    }

    private function has(string $name = ''): bool
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
