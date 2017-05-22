<?php
namespace deeka\response;

use deeka\Response;

class Jsonp extends Response
{
    protected $options = [
        'jsonp_handler'     => 'callback',
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];

    public function render($data = [])
    {
        $json = json_encode($data, $this->options['json_encode_param']);
        return "{$this->options['jsonp_handler']}({$json});";
    }
}
