<?php
namespace deeka\response;

use deeka\Response;
use deeka\Log;

class Jsonp extends Response
{
    protected $options = [
        'jsonp_handler'      => 'callback',
        'json_encode_param'  => JSON_UNESCAPED_UNICODE,
        'json_empty_to_null' => false,
    ];

    public function render($data = [])
    {
        if ($this->options['json_empty_to_null']) {
            $data = self::emptyToNull($data);
        }
        $json = json_encode($data, $this->options['json_encode_param']);
        if (\json_last_error()) {
            Log::record(\json_last_error_msg(), Log::ERROR);
        }
        return "{$this->options['jsonp_handler']}({$json});";
    }
}