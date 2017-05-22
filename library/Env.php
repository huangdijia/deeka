<?php
namespace deeka;

class Env
{
    private function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    /**
     * @param string $env_file
     * @return null
     */
    public static function init(string $env_file = '')
    {
        if ('' == $env_file) {
            $env_file = APP_ROOT . '.env';
        }
        if (!is_file($env_file)) {
            return;
        }
        $env = parse_ini_file($env_file, true);
        foreach ($env as $key => $val) {
            $name = ENV_PREFIX . strtoupper($key);
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $item = $name . '_' . strtoupper($k);
                    putenv("$item=$v");
                }
            } else {
                putenv("$name=$val");
            }
        }
    }
    /**
     * @param string $name
     * @param $default
     * @return mixed
     */
    public static function get(string $name = '', $default = null)
    {
        $result = getenv(ENV_PREFIX . strtoupper(str_replace('.', '_', $name)));
        if (false !== $result) {
            return $result;
        } else {
            return $default;
        }
    }
}
