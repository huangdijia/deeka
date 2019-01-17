<?php
namespace deeka\traits;

trait Singleton
{
    private function __construct()
    {
        // trigger_error('Constructing ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
    }

    private function __clone()
    {
        // trigger_error('Cloning ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
    }

    private function __wakeup()
    {
        // trigger_error('Unserializing ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
    }
}
