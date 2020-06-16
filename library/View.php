<?php
namespace deeka;

use deeka\traits\Singleton;
use deeka\view\Template;
use Exception;

// 視圖類
class View
{
    use Singleton;

    protected $tVar   = [];
    protected $cVar   = [];
    protected $config = [
        'layout_on'           => false,
        'layout_item'         => '{__CONTENT__}',
        'layout_name'         => 'layout',
        'tmpl_cache_on'       => true,
        'tmpl_cache_time'     => 0,
        'tmpl_parse_string'   => [],
        'tmpl_cache_path'     => './cache/',
        'tmpl_cache_suffix'   => '.php',
        'tmpl_strip_space'    => true,
        'tmpl_deny_func_list' => 'phpinfo,exec',
        'tmpl_engine'         => '',
        'tmpl_suffix'         => '.tpl.php',
        'tmpl_glue'           => '/',
        'tmpl_path'           => './view/',
        'tmpl_charset'        => 'utf-8',
        'tmpl_output_charset' => 'utf-8',
        'tmpl_var_identify'   => 'array',
    ];
    protected static $handlers = [];

    /**
     * Instance
     * @param array $config
     * @return Template
     */
    public static function instance(array $config = [])
    {
        if (empty($config)) {
            $config = Config::get('tmpl');
        }

        $key = md5(serialize($config));

        if (!isset(self::$handlers[$key])) {
            self::$handlers[$key] = new self;
            self::$handlers[$key]->setConfig($config);
        }

        return self::$handlers[$key];
    }

    /**
     * 配置參數設置
     * @param mixed $name
     * @param string $value
     * @return void
     */
    public function setConfig($name, $value = '')
    {
        if (is_array($name)) {
            $this->config = array_merge($this->config, $name);
        } else {
            $this->config[$name] = $value;
        }
    }

    /**
     * 模板變量賦值
     * @param mixed $name
     * @param string $value
     * @return void
     */
    public function assign($name, $value = '')
    {
        // 變量轉換為模板編碼
        // $name = self::autoCharset($name, $this->config['tmpl_output_charset'], $this->config['tmpl_charset']);
        // 保存變量
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     * 獲取模板變量
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Get
     * @param string $name
     * @return mixed
     */
    public function get($name = '')
    {
        if ('' === $name) {
            return $this->tVar;
        }

        return isset($this->tVar[$name]) ? $this->tVar[$name] : '';
    }

    /**
     * 自动解析模板文件
     * @param string $template
     * @return string
     */
    public function parseTemplate($template = '')
    {
        if (is_file($template)) {
            return $template;
        }

        $glue = $this->config['tmpl_glue'];

        if (
            '' == $template
            || false === strpos($template, $glue)
        ) {
            $template = Str::parseName(CONTROLLER_NAME) . $glue . Str::parseName(ACTION_NAME);
        }

        return rtrim($this->config['tmpl_path'], DS) . DS . $template . $this->config['tmpl_suffix'];
    }

    /**
     * 獲取模板內容
     * @param string $templateFile
     * @return string
     * @throws Exception
     */
    public function fetch($templateFile = '')
    {
        // 解析模板名
        $templateFile = $this->parseTemplate($templateFile);

        // 检测模板不否存在
        if (!is_file($templateFile)) {
            if (!APP_DEBUG) {
                $templateFile = strtr($templateFile, APP_PATH, '');
            }
            throw new Exception("template '{$templateFile}' is not exists", 1);
        }

        // 页面缓存
        ob_start();
        ob_implicit_flush(0);

        // 使用原生的PHP模板
        if ($this->config['tmpl_engine'] == 'php') {
            // 展开变量
            extract($this->tVar, EXTR_OVERWRITE);
            // 载入模板文件
            include $templateFile;
        } else {
            // 检查缓存是否有效
            if ($this->checkCache($templateFile)) {
                // 展开变量
                extract($this->tVar, EXTR_OVERWRITE);
                // 载入缓存文件
                $tmplCacheFile = $this->config['cache_path'] . md5($templateFile) . $this->config['cache_suffix'];
                include $tmplCacheFile;
            } else {
                // 重新解析
                $engine = new Template();
                $config = $this->config;
                unset($config['tmpl_engine']);
                $engine->setConfig($config);
                $engine->fetch($templateFile, $this->tVar);
            }
        }

        // 获取并清空缓存
        $content = ob_get_clean();

        // 替换字符串
        if (is_array($this->config['tmpl_parse_string']) && !empty($this->config['tmpl_parse_string'])) {
            $content = str_replace(array_keys($this->config['tmpl_parse_string']), array_values($this->config['tmpl_parse_string']), $content);
        }

        // 输出模板内容
        $content = trim($content);

        return $content;
    }

    /**
     * 輸出內容
     * @param string $content
     * @param string $charset
     * @param string $contentType
     * @return void
     */
    public function render($content = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        // 设置头信息
        @header('Content-Type:' . $contentType . '; charset=' . $charset);

        $content = preg_replace('/<meta[^>]+charset=[^>]+>/i', '<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />', $content);

        if ($this->config['tmpl_output_charset'] != $charset) {
            $content = self::autoCharset($content, $this->config['tmpl_output_charset'], $charset);
        }

        // 输入变量至控制台
        if (!empty($this->cVar)) {
            if (count($this->cVar) == 1) {
                $this->cVar = $this->cVar[0];
            }
            $method        = (is_array($this->cVar) || is_object($this->cVar)) ? 'dir' : 'log';
            $consoleOutput = "<script>(typeof console != 'undefined') && console.{$method}(" . json_encode($this->cVar) . ");</script>";
            $content .= $consoleOutput;
        }

        // 输出内容
        echo $content;
    }

    /**
     * 渲染模板
     * @param string $templateFile
     * @param string $charset
     * @param string $contentType
     * @return void
     * @throws Exception
     */
    public function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $content = $this->fetch($templateFile);
        $this->render($content, $charset, $contentType);
    }

    /**
     * 檢查緩存是否可用
     * @param mixed $templateFile
     * @return bool
     */
    public function checkCache($templateFile)
    {
        // 优先对配置设定检测
        if (!$this->config['tmpl_cache_on']) {
            return false;
        }

        $tmplCacheFile = $this->config['tmpl_cache_path'] . md5($templateFile) . $this->config['tmpl_cache_path'];
        $layoutFile    = $this->config['tmpl_path'] . $this->config['layout_name'] . $this->config['tmpl_suffix'];

        if (!is_file($tmplCacheFile)) {
            return false;
        } elseif ($this->config['layout_on'] && filemtime($layoutFile) > filemtime($tmplCacheFile)) {
            return false;
        } elseif (filemtime($templateFile) > filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        } elseif ($this->config['tmpl_cache_time'] != 0 && time() > filemtime($tmplCacheFile) + $this->config['tmpl_cache_time']) {
            // 缓存是否在有效期
            return false;
        }

        // 缓存有效
        return true;
    }

    /**
     * 输出到控制台
     * @param mixed $var
     * @return void
     */
    public function dump($var)
    {
        $this->cVar[] = $var;
    }

    /**
     * 判斷是否utf-8編碼
     * @param mixed $string
     * @return int|false
     */
    public static function isUtf8($string)
    {
        return preg_match('%^(?:
             [\x09\x0A\x0D\x20-\x7E]            # ASCII
           | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
           |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
           | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
           |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
           |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
           | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
           |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%xs', $string);
    }

    /**
     * 編碼轉換
     * @param mixed $fContents
     * @param string $from
     * @param string $to
     * @return mixed
     */
    public static function autoCharset($fContents, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to   = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;

        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }

        if (is_string($fContents)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($fContents, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $fContents);
            } else {
                return $fContents;
            }
        } elseif (is_array($fContents)) {
            foreach ($fContents as $key => $val) {
                $_key             = self::autoCharset($key, $from, $to);
                $fContents[$_key] = self::autoCharset($val, $from, $to);
                if ($key != $_key) {
                    unset($fContents[$key]);
                }
            }
            return $fContents;
        } else {
            return $fContents;
        }
    }
}
