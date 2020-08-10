<?php

namespace deeka\log;

interface LoggerInterface
{
    public function __construct(array $config = []);
    public function info(string $message = '', array $context = array(), string $dest = '');
}