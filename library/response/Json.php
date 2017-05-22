<?php
namespace deeka\response;

use deeka\Response;

class Json extends Response
{
    protected $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];

    public function render($data)
    {
        $json = json_encode($data, $this->options['json_encode_param']);
        // fix json php for java
        $json = str_replace(['[]', '{}', '""'], ['null', 'null', 'null'], $json);
        return $json;
    }

}