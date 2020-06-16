<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\traits\SingletonCallable;
use deeka\traits\SingletonInstance;

/**
 * @method static mixed get(string $name = '', $default = null)
 * @method static array all()
 * @method static bool has(string $name = '')
 * @method static array parse()
 * @package deeka
 */
class Options
{
    private static $instance;

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
        static $args = null;

        if (is_null($args)) {
            list($shortopts, $longopts) = $this->parse();
            $args                       = getopt($shortopts, $longopts);
        }

        return $args;
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

        foreach ($_SERVER['argv'] as $value) {
            if (preg_match('/^\-\-([\w\-]+)/', $value, $matches)) {
                $opts[1][] = $matches[1] . '::';
            } elseif (preg_match('/^\-([a-z])/', $value, $matches)) {
                $opts[0] .= $matches[1] . '::';
            }
        }

        return $opts;
    }
}
