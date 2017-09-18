<?php
use \deeka\Cache;
use \deeka\Config;
use \deeka\Cookie;
use \deeka\Debug;
use \deeka\Queue;
use \deeka\Request;
use \deeka\Response;
use \deeka\response\Xml as XmlResponse;
use \deeka\Session;

function dump($var, $echo = true, $label = null)
{
    return Debug::dump($var, $echo, $label);
}

function debug($start, $end = '', $dec = 4)
{
    if ('' == $end) {
        Debug::remark($start);
    } else {
        return 'm' == $dec ? Debug::getRangeMem($start, $end) : Debug::getRangeTime($start, $end, $dec);
    }
}

function cache(string $name, $value = '', $options = '')
{
    $cache = Cache::connect($options);
    if ('' === $value) {
        return $cache->get($name);
    } elseif (is_null($value)) {
        return $cache->rm($name);
    } else {
        if (isset($options['expire'])) {
            $expire = $options['expire'];
        } elseif (is_numeric($options)) {
            $expire = $options;
        } else {
            $expire = null;
        }
        return $cache->set($name, $value, $expire);
    }
}

function queue(string $name = '', $item = '', $options = [])
{
    // 解析指令
    if (preg_match('/^[<>\?~]/', $name)) {
        $command = substr($name, 0, 1);
        $name    = trim($name, '<>');
        switch ($command) {
            case '<':
                $func = 'lpop';
                $item = (int) $item;
                break;
            case '>':
                $func = 'lpush';
                break;
            case '?':
                $func = 'length';
                break;
            case '~':
                $func = 'clear';
                break;
        }
    } elseif (preg_match('/^[<>]/', $name)) {
        $command = substr($name, -1, 1);
        $name    = trim($name, '<>');
        switch ($command) {
            case '<':
                $func = 'rpush';
                break;
            case '>':
                $func = 'rpop';
                $item = (int) $item;
                break;
        }
    } else {
        throw new \Exception("Queue command must! eg:</>/?", 1);
    }
    $options = array_merge(
        Config::get('queue'),
        Queue::parse($options)
    );
    return Queue::connect($options)->$func($name, $item, $options);
}

function dataToXml($data, $item = 'item', $id = 'id')
{
    return XmlResponse::dataToXml($data, $item, $id);
}

function sendHttpStatus(int $code = 200)
{
    Response::instance()->sendHttpStatus($code);
}

function getClientIp(int $type = 0)
{
    return Request::ip($type);
}

function session($name = '', $value = '')
{
    if (0 < strpos($name, '.')) {
        list($namespace, $name) = explode('.', $name, 2);
    } else {
        $namespace = Config::get('session.namespace', '');
    }
    if (is_array($name)) {
        if (isset($name['namespace'])) {
            Config::set('session.namespace', $name['namespace']);
        }
        Session::init($name);
    } elseif ('' === $value) {
        $flag = substr($name, 0, 1);
        switch ($flag) {
            case '[':
                $name = substr($name, 1, -1);
                Session::operate($name, $namespace);
                break;
            case '?':
                $name = substr($name, 1);
                return Session::has($name, $namespace);
            default:
                return Session::get($name, $namespace);
                break;
        }
    } elseif (is_null($value)) {
        return Session::del($name, $namespace);
    } else {
        return Session::set($name, $value, $namespace);
    }
}

function cookie($name, $value = '', $option = null)
{
    if (is_array($name)) {
        // 初始化
        Cookie::init($name);
    } elseif (is_null($name)) {
        // 清除
        Cookie::clear($value);
    } elseif ('' === $value) {
        $flag = substr($name, 0, 1);
        switch ($flag) {
            case '?':
                // 检测
                $name = substr($name, 1);
                return Cookie::has($name);
            default:
                // 获取
                return Cookie::get($name);
                break;
        }
    } elseif (is_null($value)) {
        // 删除
        return Cookie::del($name);
    } else {
        // 设置
        return Cookie::set($name, $value, $option);
    }
}

function __()
{
    $args = func_get_args();
    array_splice($args, 1, 0, [null]);
    return call_user_func_array('\deeka\Lang::get', $args);
}