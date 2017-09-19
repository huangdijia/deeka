<?php
namespace deeka\log;

use deeka\Log;

interface ILog
{
    public function __construct($config = []);
    public function __call($name, $args);
    public function emerg($message = '');
    public function alert($message = '');
    public function crit($message = '');
    public function err($message = '');
    public function error($message = '');
    public function warn($message = '');
    public function notice($message = '');
    public function info($message = '');
    public function debug($message = '');
    public function sql($message = '');
    public function log($message = '');
    public function record($message = '', $level = Log::LOG);
    public function write($message = '', $level = Log::LOG);
    public function save();
    public function clear();
}
