<?php
namespace deeka\db;

class Builder
{
    /**
     * 预解析SQL
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public static function prepare(string $sql = '', array $params = null)
    {
        $params = self::parseParams($sql, $params);
        if (false !== strpos($sql, '?')) {
            $sql = preg_replace_callback('/[\?]/', function ($matches) use (&$params) {
                $var = array_shift($params);
                return self::parseValue($var);
            }, $sql);
        } elseif (false !== strpos($sql, ':')) {
            $sql = preg_replace_callback('/:(\w+)/', function ($matches) use ($params) {
                $name = $matches[1];
                $var  = $params[$name] ?? null;
                return self::parseValue($var);
            }, $sql);
        }
        return $sql;
    }

    /**
     * 解析参数
     * @param string $sql
     * @param array $params
     */
    public static function parseParams(string $sql = '', array $params = null)
    {
        if (is_null($params)) {
            return null;
        }
        if (false !== strpos($sql, ':')) {
            // id=:id mode
            preg_match_all('/:(\w+)/', $sql, $matches);
            $keys = $matches[1] ?? [];
            $bind = [];
            foreach ($params as $key => $value) {
                if (!in_array($key, $keys)) {
                    continue;
                }
                $bind[$key] = $value;
            }
            return $bind;
        }
        return $params;
    }

    /**
     * 解析值
     * @param mixed $value
     * @return string
     */
    public static function parseValue($value = null)
    {
        switch (gettype($value)) {
            case "boolean":
                $value = $value ? 1 : 0;
            case "integer":
            case "double":
                return $value;
            case "string":
                return "'" . addslashes($value) . "'";
            case "object":
                $value = get_object_vars($value);
            case "array":
                $value = array_map(function ($val) {
                    return self::parseValue($val);
                }, $value);
                return join(',', $value);
            case "resource":
            case "NULL":
            case "unknown type":
                return 'null';
        }
        return 'null';
    }
}
