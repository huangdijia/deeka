<?php
use deeka\Log;

// -32700 => '解析错误', // 服务器接收到无效的JSON OR 服务器解析JSON文本发生错误。
// -32600 => '无效的请求', // 发送的JSON不是一个有效的请求。
// -32601 => '方法未找到', //方法不存在或不可见。
// -32602 => '无效的参数', //无效的方法参数。
// -32603 => '内部错误', //JSON-RPC内部错误。
// -32000 to -32099 => 服务器端错误 //保留给具体实现定义服务器端错误。

class jsonRPCServer
{
    const JSONRPC_VERSION = '2.0';
    /**
     * @var mixed 請求參數
     */
    protected static $request = null;
    private static $debug     = false;

    /**
     * @param $errno 錯誤編碼
     * @param $errstr 錯誤信息
     * @param $errfile 文件路徑
     * @param $errline 文件行數
     * @param array $errcontext 內容
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext = [])
    {
        self::$debug && Log::record(__METHOD__, Log::DEBUG);
        $log = "{$errstr} [{$errno}] in {$errfile} on line {$errline}";
        // 记录日志
        Log::record($log, $errno);
        $response = [
            'jsonrpc' => self::JSONRPC_VERSION,
            'result'  => null,
            'error'   => $errstr,
            'id'      => self::$request['id'],
        ];
        if (
            (error_reporting() & $errno)
            && self::isFatal($errno)
        ) {
            // 输出返回结果
            self::response($response);
            // 保存日誌
            Log::save();
            exit;
        }
    }

    /**
     * @param $e 異常對象
     */
    public static function exceptionHandler($e)
    {
        self::$debug && Log::record(__METHOD__, Log::DEBUG);
        $error            = [];
        $error['message'] = $e->getMessage();
        $trace            = $e->getTraceAsString();
        $error['file']    = $e->getFile();
        $error['line']    = $e->getLine();
        $log              = "{$error['message']} in {$error['file']} on line {$error['line']}";
        $log .= PHP_EOL . PHP_EOL . 'Track:' . PHP_EOL . $trace;
        Log::record($log, Log::ERR);
        $response = [
            'jsonrpc' => self::JSONRPC_VERSION,
            'result'  => null,
            'error'   => $e->getMessage(),
            'id'      => self::$request['id'],
        ];
        // 输出返回结果
        self::response($response);
        // 保存日誌
        Log::save();
        exit;
    }

    public static function shutdownHandler()
    {
        self::$debug && Log::record(__METHOD__, Log::DEBUG);
        if (
            !is_null($error = error_get_last())
            && self::isFatal($error['type'])
        ) {
            $errstr  = $error['message'];
            $errno   = $error['type'];
            $errfile = $error['file'];
            $errline = $error['line'];
            $log     = "{$errstr} [{$errno}] in {$errfile} on line {$errline}";
            // 记录日志
            Log::record($log, $errno);
            $response = [
                'jsonrpc' => self::JSONRPC_VERSION,
                'result'  => null,
                'error'   => $errstr,
                'id'      => self::$request['id'],
            ];
            // 输出返回结果
            self::response($response);
            // 保存日誌
            Log::save();
            exit;
        }
    }

    /**
     * @param $type 錯誤類型
     */
    protected static function isFatal($type = 0)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * @param $object 對象
     */
    public static function handle($object)
    {
        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, 'exceptionHandler']);
        register_shutdown_function([__CLASS__, 'shutdownHandler']);
        // 检测是否 JSON-RCP 请求
        if (
            $_SERVER['REQUEST_METHOD'] != 'POST' ||
            empty($_SERVER['CONTENT_TYPE']) ||
            $_SERVER['CONTENT_TYPE'] != 'application/json'
        ) {
            // 非 JSON-RPC 请求
            return false;
        }
        // 从 input 获取请求数据
        self::$request = $request = json_decode(file_get_contents('php://input'), true);
        // 記錄請求方法
        $request_string = var_export($request, 1);
        $request_string = preg_replace('/\s+/', ' ', $request_string);
        // $request_string = strtr($request_string, ['array ( ' => '[ ', ', )' => ' ]']);
        Log::record('[JSONRPC REQUEST=' . $request_string . ']', Log::INFO);
        // 执行请求
        try {
            // if ($result = @call_user_func_array([$object, $request['method']], $request['params'])) {
            //     $response = [
            //         'jsonrpc' => self::JSONRPC_VERSION,
            //         'result'  => $result,
            //         'error'   => null,
            //         'id'      => $request['id'],
            //     ];
            // } else {
            //     $response = [
            //         'jsonrpc' => self::JSONRPC_VERSION,
            //         'result'  => null,
            //         'error'   => 'unknown method or incorrect parameters',
            //         'id'      => $request['id'],
            //     ];
            // }
            $result   = @call_user_func_array([$object, $request['method']], $request['params']);
            $response = [
                'jsonrpc' => self::JSONRPC_VERSION,
                'result'  => $result,
                'error'   => null,
                'id'      => $request['id'],
            ];
        } catch (Exception $e) {
            self::exceptionHandler($e);
        }
        // 输出返回结果
        self::response($response);
        // 执行完成
        return true;
    }

    /**
     * @param array $response 返回數據
     * @return null
     */
    public static function response(array $response = [])
    {
        if (empty(self::$request['id'])) {
            return;
        }
        // 記錄日誌
        Log::record('[JSONRPC RESPONSE=' . preg_replace('/\s+/', ' ', var_export($response, 1)) . ']', Log::INFO);
        // 結果輸出
        header('content-type: text/javascript');
        echo json_encode($response);
    }
}
