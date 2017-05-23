<?php
namespace deeka\Controller;

use deeka\Loader;

class JsonRpc
{

    /**
     * 架构函数
     * @access public
     */
    public function __construct()
    {
        // 控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }
        // 导入类库
        Loader::addClassMap('jsonRPCServer', CORE_PATH . '/vendor/jsonRPC/jsonRPCServer.php');
        // 启动server
        try {
            \jsonRPCServer::handle($this);
        } catch (Exception $e) {
            throw $e;
        }
        // 退出框架流程
        exit;
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method, $args)
    {}
}
