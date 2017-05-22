<?php
namespace deeka\response;

use deeka\Response;

class Xml extends Response
{
    protected $options = [
        'root_node' => 'root',
        'root_attr' => '',
        'item_node' => 'item',
        'item_key'  => 'id',
        'encoding'  => 'utf-8',
    ];

    public static function xmlEncode($data = [], $root = 'root', $item = 'item', $attr = [], $id = 'id', $encoding = 'utf-8')
    {
        if (is_array($attr)) {
            $array = [];
            foreach ($attr as $key => $value) {
                $array[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $array);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}{$attr}>";
        $xml .= self::dataToXml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
    }

    public static function dataToXml($data = [], $item = 'item', $id = 'id')
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id && $attr = " {$id}=\"{$key}\"";
                $key         = $item;
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= (is_array($val) || is_object($val)) ? self::dataToXml($val, $item, $id) : $val;
            $xml .= "</{$key}>";
        }
        return $xml;
    }

    public function render($data)
    {
        return self::xmlEncode(
            $data,
            $this->options['root_node'],
            $this->options['item_node'],
            $this->options['root_attr'],
            $this->options['item_key'],
            $this->options['encoding']
        );
    }
}
