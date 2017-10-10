<?php
namespace deeka\view;

use Exception;

// 模板引擎
class Template
{
    private $literal    = [];
    private $block      = [];
    private $tVar       = [];
    private $config     = [];
    private $comparison = [
        ' nheq ' => ' !== ',
        ' heq '  => ' === ',
        ' neq '  => ' != ',
        ' eq '   => ' == ',
        ' egt '  => ' >= ',
        ' gt '   => ' > ',
        ' elt '  => ' <= ',
        ' lt '   => ' < ',
    ];
    private static $strip = [
        '\\"'  => '"',
        "\\'"  => "'",
        '\\\\' => '\\',
        '\\0'  => '',
    ];

    // 实现转义
    protected function stripslashes($str)
    {
        $find    = array_keys(self::$strip);
        $replace = array_values(self::$strip);
        return str_replace($find, $replace, $str);
    }

    // 參數配置
    public function setConfig($name, $value = '')
    {
        if (is_array($name)) {
            $this->config = array_merge($this->config, $name);
        } else {
            $this->config[$name] = $value;
        }
    }

    // 設置模板變量
    public function set($name, $value = '')
    {
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } else {
            $this->tVar[$name] = $value;
        }
    }

    // 獲取模板變量
    public function __get($name)
    {
        return $this->get($name);
    }

    public function get($name)
    {
        if (isset($this->tVar[$name])) {
            return $this->tVar[$name];
        } else {
            return '';
        }
    }

    // 獲取模板輸出內容
    public function fetch($templateFile, $templateVar)
    {
        // 缓存变量
        $this->set($templateVar);
        // 生成缓存文件
        $templateCacheFile = $this->loadTemplate($templateFile);
        // 展开变量
        extract($templateVar, EXTR_OVERWRITE);
        // 载入缓存文件
        include $templateCacheFile;
    }

    // 加載模板
    protected function loadTemplate($templateFile)
    {
        // 载入模板内容
        if (is_file($templateFile)) {
            $templateContent = file_get_contents($templateFile);
        }
        // 定义缓存文件名
        $templateCacheFile = $this->config['tmpl_cache_path'] . md5($templateFile) . $this->config['tmpl_cache_suffix'];
        // 检测缓存目录
        $dir = dirname($templateCacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // 判断是否启用布局
        if ($this->config['layout_on']) {
            if (false !== strpos($templateContent, '{__NOLAYOUT__}')) {
                // 可以单独定义不使用布局
                $templateContent = str_replace('{__NOLAYOUT__}', '', $templateContent);
            } else {
                // 替换布局的主体内容
                // 布局模板名称
                $layoutFile = $this->config['tmpl_path'] . $this->config['layout_name'] . $this->config['tmpl_suffix'];
                // 载入布局模板内容
                if (is_file($layoutFile)) {
                    $layoutContent   = file_get_contents($layoutFile);
                    $templateContent = str_replace($this->config['layout_item'], $templateContent, $layoutContent);
                }
            }
        }
        // 编译模板
        $templateContent = $this->compiler($templateContent);
        // 生成utf-8編碼的緩存
        if ($this->config['tmpl_charset'] != $this->config['tmpl_output_charset']) {
            $templateContent = @iconv($this->config['tmpl_charset'], $this->config['tmpl_output_charset'], $templateContent);
        }
        // 写入缓存文件
        if (false === file_put_contents($templateCacheFile, $templateContent)) {
            throw new Exception("Create template cache failed");
        }
        return $templateCacheFile;
    }

    // 編譯內容
    protected function compiler($content)
    {
        // 解析模板内容
        $content = $this->parse($content);
        // 替换{block}
        $content = preg_replace("/\{block\s[^\}]+\}(.*?)\{\/block\}/is", '\\1', $content);
        // 特殊处理switch
        $content = preg_replace('/(\sswitch[^:]+:)[^:]+(case[^:]+:)/is', '\\1 \\2', $content);
        $content = preg_replace('/(break;)[^:]+(case|default|endswitch)/is', '\\1 \\2', $content);
        // 解析普通模板标签 {tagName}
        // 修正对JS/JQUERY的支持
        $content = preg_replace_callback('/(\{)([^\d\s\{\}].+?)(\})/is', function ($m) {
            return $this->parseTag($m[2]);
        }, $content);
        // 还原literal
        $content = preg_replace_callback('/<!--###literal(\d+)###-->/is', function ($m) {
            return $this->restoreLiteral($m[1]);
        }, $content);
        // 去掉空白php標記
        $content = preg_replace('/<\?(php)?\s*?\?>/is', '', $content);
        // 返回内容
        return $content;
    }

    // 解析內容
    protected function parse($content)
    {
        // 解析include
        $content = $this->parseInclude($content);
        // 解析literal
        $content = preg_replace_callback('/\{literal\}(.*?)\{\/literal\}/is', function ($m) {
            return $this->parseLiteral($m[1]);
        }, $content);
        // 解析语法
        $content = $this->parsePhpSyntax($content);
        if ($this->config['tmpl_strip_space']) {
            /* 去除html空格与换行 */
            $find    = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $content = preg_replace($find, $replace, $content);
        }
        // 优化PHP代码
        $content = $this->stripWhitespace($content);
        // 返回内容
        return $content;
    }

    // 兼容編譯
    protected function compat($content)
    {
        // 兼容include
        $content = $this->compatInclude($content);
        // 兼容原生PHP
        $content = $this->compatPhp($content);
        // 返回內容
        return $content;
    }

    // 兼容include
    protected function compatInclude($content)
    {
        // 兼容$this->loadTmplate
        $content = preg_replace_callback('/\$this\->loadTmplate\s*\((.*?)\);/is', function ($m) {
            return $this->compatPhpInclude($m[1]);
        }, $content);
        // 兼容原生include或require
        $content = preg_replace_callback('/(include|require|include_once|require_once)\(([^;]+)\);/is', function ($m) {
            return $this->compatPhpInclude($m[2], $m[1]);
        }, $content);
        $content = preg_replace_callback('/(include|require|include_once|require_once)\s(?!file=)([^;]+);/is', function ($m) {
            return $this->compatPhpInclude($m[2], $m[1]);
        }, $content);
        // 返回內容
        return $content;
    }

    // {include}取代$this->loadTmplate和include
    protected function compatPhpInclude($includeFile, $includeTag = null)
    {
        $includeFile = self::stripslashes($includeFile);
        // 兼容PHP動態include
        // if(strpos($includeFile, '$') > 0){
        // if(null==$includeTag) $includeTag = 'include';
        //     return $includeTag.'('.$includeFile.'); ';
        // }
        // 替換includeFile
        $replaces = [
            ' '              => '',
            '"'              => '',
            "'"              => '',
            ')'              => '',
            '('              => '',
            'APP_PATH.'      => APP_PATH,
            'TEMPLATE_PATH.' => TMPL_PATH,
        ];
        $includeFile = str_replace(array_keys($replaces), array_values($replaces), $includeFile);
        $includeFile = preg_replace_callback('/\$(\w+)/is', function ($m) {
            return $this->get($m[1]);
        }, $includeFile);
        $parseStr    = ' ?>{include file="' . $includeFile . '"}<?php ';
        return $parseStr;
    }

    // 兼容原生的php代碼
    protected function compatPhp($content)
    {
        $content = preg_replace_callback('/<\?(.*?)\?>/is', function ($m) {
            return $this->compatLiteralPhp($m[1]);
        }, $content);
        return $content;
    }

    // 原生php代碼保持原樣
    protected function compatLiteralPhp($content)
    {
        // 替换each($this->var)为each($var)
        $content = preg_replace('/each\(\s*\$this\->([\w\[\]\'\_\-\$]+)\s*\)/is', 'each(\$\\1)', $content);
        // 返回literal包含的格式
        return '{literal}<?' . self::stripslashes($content) . '?>{/literal}';
    }

    // 解析{}格式
    protected function parseTag($tagStr)
    {
        // 反转义
        // 解决gbk会被转义坏的问题
        $tagStr = self::stripslashes($tagStr);
        //还原非模板标签
        if (preg_match('/^[\s|\d]/is', $tagStr)) {
            //过滤空格和数字打头的标签
            return '{' . $tagStr . '}';
        }
        $flag  = substr($tagStr, 0, 1);
        $flag2 = substr($tagStr, 1, 1);
        $name  = substr($tagStr, 1);
        // 快捷输出
        switch ($flag) {
            case '@': // SESSION
                $flag = '$';
                $name = '_SESSION.' . $name;
                break;
            case '#': // COOKIE
                $flag = '$';
                $name = '_COOKIE.' . $name;
                break;
            case '.': // GET
                $flag = '$';
                $name = '_GET.' . $name;
                break;
            case '^': // POST
                $flag = '$';
                $name = '_POST.' . $name;
                break;
            case '*': // CONST
                return '<?php echo defined(\'' . $name . '\')?' . $name . ':\'\';?>';
                break;
        }
        // 解析变量
        if ('$' == $flag && !in_array($flag2, ['.', '('])) {
            //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        } elseif ('-' == $flag || '+' == $flag) {
            // 输出计算
            return '<?php echo ' . $flag . $name . ';?>';
        } elseif (':' == $flag) {
            // 输出某个函数的结果
            return '<?php echo ' . $name . ';?>';
        } elseif ('~' == $flag) {
            // 执行某个函数
            return '<?php ' . $name . ';?>';
        } elseif (substr($tagStr, 0, 2) == '//' || (substr($tagStr, 0, 2) == '/*' && substr($tagStr, -2) == '*/')) {
            //注释标签
            return '';
        }
        // 未识别的标签直接返回
        return '{' . $tagStr . '}';
    }

    // 解析模板變量
    protected function parseVar($varStr)
    {
        $varStr               = trim($varStr);
        static $_varParseList = [];
        //如果已经解析过该变量字串，则直接返回变量值
        if (isset($_varParseList[$varStr])) {
            return $_varParseList[$varStr];
        }
        $parseStr  = '';
        $varExists = true;
        if (!empty($varStr)) {
            $varArray = explode('|', $varStr);
            //取得变量名称
            $var = array_shift($varArray);
            if (false !== strpos($var, '.')) {
                //支持 {$var.property}
                $vars = explode('.', $var);
                $var  = array_shift($vars);
                switch (strtolower($this->config['tmpl_var_identify'])) {
                    case 'array': // 识别为数组
                        $name = '$' . $var;
                        foreach ($vars as $key => $val) {
                            $name .= '["' . $val . '"]';
                        }

                        break;
                    case 'object': // 识别为对象
                        $name = '$' . $var;
                        foreach ($vars as $key => $val) {
                            $name .= '->' . $val;
                        }

                        break;
                    default: // 自动判断数组或对象 只支持二维
                        $name = 'is_array($' . $var . ') ? $' . $var . '["' . $vars[0] . '"] : $' . $var . '->' . $vars[0];
                }
            } elseif (false !== strpos($var, '[')) {
                //支持 {$var['key']} 方式输出数组
                $name = "$" . $var;
                preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
                $var = $match[1];
            } elseif (false !== strpos($var, ':') && false === strpos($var, '::') && false === strpos($var, '?')) {
                //支持 {$var:property} 方式输出对象的属性
                $vars = explode(':', $var);
                $var  = str_replace(':', '->', $var);
                $name = "$" . $var;
                $var  = $vars[0];
            } else {
                $name = "$$var";
            }
            //对变量使用函数
            if (count($varArray) > 0) {
                $name = $this->parseVarFunction($name, $varArray);
            }
            $parseStr = '<?php echo (' . $name . '); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    // 解析模板變量帶的函數
    protected function parseVarFunction($name, $varArray)
    {
        // 对变量使用函数
        $length = count($varArray);
        // 取得模板禁止使用函数列表
        $template_deny_funs = explode(',', $this->config['tmpl_deny_func_list']);
        for ($i = 0; $i < $length; $i++) {
            $args = explode('=', $varArray[$i], 2);
            // 模板函数过滤
            $fun = strtolower(trim($args[0]));
            switch ($fun) {
                case 'default': // 特殊模板函数
                    $name = '(' . $name . ') ? (' . $name . ') : ' . $args[1];
                    break;
                default: // 通用模板函数
                    if (!in_array($fun, $template_deny_funs)) {
                        if (isset($args[1])) {
                            if (strstr($args[1], '###')) {
                                $args[1] = str_replace('###', $name, $args[1]);
                                $name    = "$fun($args[1])";
                            } else {
                                $name = "$fun($name, $args[1])";
                            }
                        } else if (!empty($args[0])) {
                            $name = "$fun($name)";
                        }
                    }
            }
        }
        return $name;
    }

    // 解析原樣輸出
    private function parseLiteral($content)
    {
        if (trim($content) == '') {
            return '';
        }

        $content           = self::stripslashes($content);
        $i                 = count($this->literal);
        $parseStr          = "<!--###literal{$i}###-->";
        $this->literal[$i] = $content;
        return $parseStr;
    }

    // 恢復原樣輸出
    private function restoreLiteral($index)
    {
        // 还原literal标签
        $parseStr = $this->literal[$index];
        // 销毁literal记录
        unset($this->literal[$index]);
        return $parseStr;
    }

    // 解析模板中的布局标签
    protected function parseLayout($content)
    {
        // 读取模板中的布局标签
        $find = preg_match('/\{layout\s(.+?)\s*?\}/is', $content, $matches);
        if ($find) {
            //替换Layout标签
            $content = str_replace($matches[0], '', $content);
            //解析Layout标签
            $array = $this->parseXmlAttrs($matches[1]);
            if (
                !$this->config['layout_on']
                || $this->config['layout_name'] != $array['name']
            ) {
                // 读取布局模板
                $layoutFile = $this->config['tmpl_path'] . $array['name'] . $this->config['tmpl_suffix'];
                $replace    = $array['replace'] ?? $this->config['layout_item'];
                // 替换布局的主体内容
                $content = str_replace($replace, $content, file_get_contents($layoutFile));
            }
        } else {
            $content = str_replace('{__NOLAYOUT__}', '', $content);
        }
        return $content;
    }

    // 解析include標籤
    private function parseInclude($content)
    {
        // 兼容解析
        $content = $this->compat($content);
        // 解析布局
        $content = $this->parseLayout($content);
        // 解析继承
        $content = $this->parseExtend($content);
        // 读取模板中的include标签
        $pattern = "/\{include\s([^\}]+)\}/is";
        $find    = preg_match_all($pattern, $content, $matches);
        if ($find) {
            for ($i = 0; $i < $find; $i++) {
                $attrs       = $matches[1][$i];
                $vars        = $this->parseXmlAttrs($attrs);
                $includeFile = $vars['file'];
                unset($vars['file']);
                $content = str_replace($matches[0][$i], $this->parseIncludeItem($includeFile, $vars), $content);
            }
        }
        // 返回内容
        return $content;
    }

    // 解析include模板
    private function parseIncludeItem($includeFile, $vars = [])
    {
        // 支持加載變量文件名
        if ('$' == substr($includeFile, 0, 1)) {
            $includeFile = $this->get(substr($includeFile, 1));
        }
        // 分析模板文件名并读取内容
        if (false === strpos($includeFile, '.')) {
            $includeFile .= $this->config['tmpl_suffix'];
        }
        if (!is_file($includeFile)) {
            $includeFile = $this->config['tmpl_path'] . $includeFile;
        }
        if (!is_file($includeFile)) {
            return '';
        }
        $content = file_get_contents($includeFile);
        // 替换变量
        foreach ($vars as $key => $val) {
            $content = str_replace('[' . $key . ']', $val, $content);
        }
        // 再次对包含文件进行模板分析
        $content = $this->parseInclude($content);
        return $content;
    }

    // 解析繼承模板
    public function parseExtend($content)
    {
        // 储存block
        $regex   = '/\{block\s([^\}]+)\}(.*?)\{\/block\}/is';
        $content = preg_replace_callback($regex, function ($m) {
            return $this->parseBlock($m[1], $m[0]);
        }, $content);
        // 查找父模板
        $pattern = '/\{extend\s([^\}]+)\}/i';
        $find    = preg_match($pattern, $content, $matches);
        if ($find) {
            // 替换extend标签为空
            $content = str_replace($matches[0], '', $content);
            // 解析父模板名称
            $attrs = $this->parseXmlAttrs($matches[1]);
            // 组装父模板路径
            $exFile = $this->config['tmpl_path'] . $attrs['name'] . $this->config['tmpl_suffix'];
            // 获取父模板内容
            $content = file_get_contents($exFile);
            // 递归解析
            $content = $this->parseExtend($content);
        }
        // 还原block
        $content = preg_replace_callback($regex, function ($m) {
            return $this->replaceBlock($m[1], $m[0]);
        }, $content);
        // 返回内容
        return $content;
    }

    // 解析block標籤及保存block內容
    public function parseBlock($attrs, $content)
    {
        $attrs   = self::stripslashes($attrs);
        $attrs   = $this->parseXmlAttrs($attrs);
        $name    = $attrs['name'];
        $content = self::stripslashes($content);
        if (!isset($this->block[$name])) {
            $this->block[$name] = $content;
        }
        return $content;
    }

    // 替換block內容
    public function replaceBlock($attrs, $content)
    {
        $attrs = self::stripslashes($attrs);
        $attrs = $this->parseXmlAttrs($attrs);
        $name  = $attrs['name'];
        if (isset($this->block[$name])) {
            $content = $this->block[$name];
        }
        $content = self::stripslashes($content);
        return $content;
    }

    // 解析php語法
    private function parsePhpSyntax($content)
    {
        $pattern = "/\{(if|elseif|foreach|for|defined|notdefined|empty|notempty|isset|notisset|present|notpresent|assign|switch|case|eq|neq|gt|egt|lt|elt|heq|nheq|in|notin|between|notbetween|css|js)\s([^\}]+)\}/is";
        $num     = preg_match_all($pattern, $content, $matches);
        if ($num) {
            for ($i = 0; $i < $num; $i++) {
                $tag  = strtolower($matches[1][$i]);
                $attr = $this->parseXmlAttrs($matches[2][$i]);
                $find = $matches[0][$i];
                switch ($tag) {
                    case 'if':
                    case 'elseif':
                        $condition = $attr['condition'] ?? '';
                        if ('' == $condition) {
                            throw new Exception("'condition' of IF is undefined");
                        }
                        $condition = str_ireplace(
                            array_keys($this->comparison),
                            array_values($this->comparison),
                            $condition
                        );
                        $replace   = '<?php ' . $tag . '(' . $condition . '):?>';
                        break;
                    case 'foreach':
                        $name = $attr['name'] ?? '';
                        $as   = $attr['as'] ?? 'vo';
                        $key  = $attr['key'] ?? '' ?: 'key';
                        $loop = $attr['loop'] ?? '' ?: '_i';
                        if ('' == $name) {
                            throw new Exception("'name' of FOREACH is undefined");
                        }
                        if ('' == $as) {
                            throw new Exception("'as' of FOREACH is undefined");
                        }
                        $replace = '<?php $' . $loop . '=0;foreach((array)$' . $name . ' as $' . $key . '=>$' . $as . '):$' . $loop . '++;?>';
                        break;
                    case 'for':
                        $name  = $attr['name'] ?? '' ?: 'i';
                        $start = $attr['start'] ?? '' ?: 0;
                        $end   = $attr['end'] ?? '' ?: '';
                        if ('' == $end) {
                            throw new Exception("'end' of FOR is undefined");
                        }
                        $step       = $attr['step'] ?? '' ?: 1;
                        $comparison = $attr['comparison'] ?? '' ?: 'lt';
                        $comparison = $this->comparison[' ' . $comparison . ' '];
                        $replace    = '<?php for($' . $name . ' = ' . $start . '; $' . $name . $comparison . $end . '; $' . $name . '+=' . $step . '):?>';
                        break;
                    case 'switch':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of SWITCH is undefined");
                        }
                        $replace = '<?php switch($' . $name . '):?>';
                        break;
                    case 'case':
                        if (!isset($attr['value'])) {
                            throw new Exception("'value' of CASE is undefined");
                        }
                        $value   = $attr['value'];
                        $replace = '<?php case "' . $value . '":?>';
                        break;
                    case 'case':
                        $replace = '<?php default:?>';
                        break;
                    case 'eq':
                    case 'neq':
                    case 'gt':
                    case 'egt':
                    case 'lt':
                    case 'elt':
                    case 'heq':
                    case 'nheq':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        if (!isset($attr['value'])) {
                            throw new Exception("'value' of " . strtoupper($tag) . " is undefined");
                        }
                        $value      = $attr['value'];
                        $comparison = $this->comparison[' ' . $tag . ' '];
                        $replace    = '<?php if($' . $name . $comparison . '"' . $value . '"):?>';
                        break;
                    case 'in':
                    case 'notin':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        if (!isset($attr['value'])) {
                            throw new Exception("'value' of " . strtoupper($tag) . " is undefined");
                        }
                        $value    = $attr['value'];
                        $rangeVar = 'explode(\',\', \'' . $value . '\')';
                        $replace  = '<?php if(' . ($tag == 'notin' ? '!' : '') . 'in_array($' . $name . ', ' . $rangeVar . ')):?>';
                        break;
                    case 'between':
                    case 'notbetween':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        if (!isset($attr['value'])) {
                            throw new Exception("'value' of " . strtoupper($tag) . " is undefined");
                        }
                        $value    = $attr['value'];
                        $rangeVar = '$_RANGE_VAR=explode(\',\', \'' . $value . '\');';
                        $replace  = '<?php ' . $rangeVar . ' if(' . ($tag == 'notbetween' ? '!' : '') . '($_RANGE_VAR[0]<$' . $name . ' && $' . $name . '<$_RANGE_VAR[1])):?>';
                        break;
                    case 'defined':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        $replace = '<?php if(defined(' . $name . ')):?>';
                        break;
                    case 'notdefined':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        $replace = '<?php if(!defined(' . $name . ')):?>';
                        break;
                    case 'present':
                        $tag = 'isset';
                    case 'empty':
                    case 'isset':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        $replace = '<?php if(' . $tag . '($' . $name . ')):?>';
                        break;
                    case 'notpresent':
                        $tag = 'notisset';
                    case 'notempty':
                    case 'notisset':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        $replace = '<?php if(!' . str_replace('not', '', $tag) . '($' . $name . ')):?>';
                        break;
                    case 'assign':
                        $name = $attr['name'] ?? '';
                        if ('' == $name) {
                            throw new Exception("'name' of " . strtoupper($tag) . " is undefined");
                        }
                        if (!isset($attr['value'])) {
                            throw new Exception("'value' of " . strtoupper($tag) . " is undefined");
                        }
                        $value   = $attr['value'];
                        $replace = '<?php $' . $name . '="' . $value . '";?>';
                        break;
                    case 'js':
                        $href = $attr['href'] ?? '';
                        if ('' == $href) {
                            throw new Exception("'href' of " . strtoupper($tag) . " is undefined");
                        }
                        $type    = $attr['type'] ?? '' ?: "text/javascript";
                        $replace = '<script type="' . $type . '" src="' . $href . '"></script>';
                        break;
                    case 'css':
                        $href = $attr['href'] ?? '';
                        if ('' == $href) {
                            throw new Exception("'href' of " . strtoupper($tag) . " is undefined");
                        }
                        $rel     = $attr['rel'] ?? '' ?: "stylesheet";
                        $type    = $attr['type'] ?? '' ?: "text/css";
                        $replace = '<link href="' . $href . '" rel="' . $rel . '" type="' . $type . '">';
                        break;
                }
                $content = str_replace($find, $replace, $content);
            }
            $pattern = "/\{(default|else)\}/is";
            $content = preg_replace($pattern, '<?php \\1:?>', $content);
            $pattern = "/\{\/(if|defined|notdefined|empty|notempty|isset|notisset|present|notpresent|eq|neq|gt|egt|lt|elt|heq|nheq|in|notin|between|notbetween)\}/is";
            $content = preg_replace($pattern, '<?php endif;?>', $content);
            $pattern = "/\{\/(foreach|for|switch)\}/is";
            $content = preg_replace($pattern, '<?php end\\1;?>', $content);
            $pattern = "/\{\/(case|default)\}/is";
            $content = preg_replace($pattern, '<?php break;?>', $content);
        }
        // 返回内容
        return $content;
    }

    // xml格式解析標籤屬性
    private function parseXmlAttrs($attrs)
    {
        $xmlstr = '<tpl><tag ' . $attrs . ' /></tpl>';
        $xml    = simplexml_load_string($xmlstr);
        if (!$xml) {
            throw new Exception('Template syntax error');
        }
        $xml   = (array) ($xml->tag->attributes());
        $array = array_change_key_case($xml['@attributes']);
        return $array;
    }

    // 去除空白
    private function stripWhitespace($content)
    {
        $stripStr = '';
        //分析php源码
        $tokens     = token_get_all($content);
        $last_space = false;
        for ($i = 0, $j = count($tokens); $i < $j; $i++) {
            if (is_string($tokens[$i])) {
                $last_space = false;
                $stripStr .= $tokens[$i];
            } else {
                switch ($tokens[$i][0]) {
                    //过滤各种PHP注释
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    //过滤空格
                    case T_WHITESPACE:
                        if (!$last_space) {
                            $stripStr .= ' ';
                            $last_space = true;
                        }
                        break;
                    case T_START_HEREDOC:
                        $stripStr .= "<<<HTML\n";
                        break;
                    case T_END_HEREDOC:
                        $stripStr .= "HTML;\n";
                        for ($k = $i + 1; $k < $j; $k++) {
                            if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                                $i = $k;
                                break;
                            } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                        break;
                    default:
                        $last_space = false;
                        $stripStr .= $tokens[$i][1];
                }
            }
        }
        return $stripStr;
    }
}
