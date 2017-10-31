<?php
namespace deeka\response;

use deeka\Response;
use deeka\Log;

class Json extends Response
{
    protected $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];

    public function render($data)
    {
        $json = json_encode($data, $this->options['json_encode_param']);
        if (\json_last_error()) {
            Log::record(\json_last_error_msg(), Log::ERR);
        }
        // fix json php for java
        $json = str_replace(['[]', '{}', '""'], ['null', 'null', 'null'], $json);
        return $json;
    }

}