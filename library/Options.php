<?php
namespace deeka;

class Options
{
    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    public static function get(string $name = '', $default = null)
    {
        static $argv = null;
        if (is_null($argv)) {
            [$shortopts, $longopts] = self::parse();
            $argv                   = getopt($shortopts, $longopts);
        }
        if ('' == $name) {
            return $argv;
        }
        return $argv[$name] ?? $default;
    }

    private static function parse(): array
    {
        if (empty($_SERVER['argv'])) {
            return [];
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