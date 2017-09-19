<?php
namespace deeka\cache;

interface ICache
{
    public function __construct($options = []);
    public function get($name);
    public function set($name, $value, $expire);
    public function rm($name);
    public function clear();
}
