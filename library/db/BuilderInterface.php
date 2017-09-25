<?php
namespace deeka\db;

interface BuilderInterface
{
    public function __construct();
    public function select(array $options);
    public function insert(array $data, array $options, $replace);
    public function update(array $data, array $options);
    public function delete(array $options);
}