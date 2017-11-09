<?php
namespace deeka\db\driver;

use Closure;
use deeka\Cache;
use deeka\Db;
use deeka\db\builder\Mysql as Builder;
use deeka\db\Query;
use deeka\Debug;
use deeka\Log;
use Exception;
use PDO;
use PDOException;

class Mysql
{
    /**
     * 链接 ID
     * @var mixed
     */
    private $linkid = null;
    /**
     * 当前 PDO
     * @var mixed
     */
    private $dbh = null;
    /**
     * @var mixed
     */
    private $stmt = null;
    /**
     * SQL
     * @var mixed
     */
    private $_sql = null;
    /**
     * PDO 集合
     * @var array
     */
    private $dbhs = [];
    /**
     * 配置
     * @var array
     */
    private $config = [];
    /**
     * 选项
     * @var array
     */
    private $options = [];
    /**
     * 错误信息
     * @var mixed
     */
    private $_error = null;
    /**
     * 错误编码
     * @var mixed
     */
    private $_errno = null;
    /**
     * 影响行数
     * @var int
     */
    private $_affectrows = 0;
    /**
     * 最后插入ID
     * @var int
     */
    private $_insertid = 0;
    /**
     * 查询器
     * @var mixed
     */
    private $_query = null;
    /**
     * 方法别名
     * @var array
     */
    private $_methodAlias = [
        'selectOne' => 'find',
        'first'     => 'find',
        'selectAll' => 'select',
    ];

    /**
     * 构造
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config['options'] = array_merge(
            [
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_STRINGIFY_FETCHES        => false,
                PDO::ATTR_EMULATE_PREPARES         => false,
                PDO::ATTR_CASE                     => PDO::CASE_NATURAL,
                PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_ORACLE_NULLS             => PDO::NULL_NATURAL,
            ],
            $config['options'] ?? []
        );
        $config = array_merge(
            [
                'type'       => 'mysql',
                'host'       => '127.0.0.1',
                'port'       => 3306,
                'user'       => 'root',
                'pwd'        => '',
                'dbname'     => '',
                'charset'    => 'utf8',
                'persistent' => false,
            ],
            $config
        );
        $this->config = $config;
    }

    /**
     * @param $method
     * @param $args
     */
    public function __call($method, $args)
    {
        // 方法别名
        if (in_array($method, array_keys($this->_methodAlias))) {
            return call_user_func_array([$this, $this->_methodAlias[$method]], $args);
        }
        // 实例化查询器
        if (is_null($this->_query)) {
            $this->_query = Query::instance();
        }
        try {
            call_user_func_array([$this->_query, $method], $args);
        } catch (Exception $e) {
            throw new Exception(__CLASS__ . "::{$method}() is not exists", 1);
        }
        return $this;
    }

    /**
     * 解析配置
     * @param array $config
     */
    private function _parseConfig(array $config = [])
    {
        $options = $config['options'] ?? [];
        // 支持集群，格式'host'=>'host1,host2,host3'
        if (false !== strpos($config['host'], ',')) {
            $types       = explode(',', $config['type']);
            $hosts       = explode(',', $config['host']);
            $ports       = explode(',', $config['port']);
            $users       = explode(',', $config['user']);
            $pwds        = explode(',', $config['pwd']);
            $dbnames     = explode(',', $config['dbname']);
            $charsets    = explode(',', $config['charset']);
            $persistents = explode(',', $config['persistent']);
            $index       = array_rand($hosts);
            $config      = [
                'type'       => $types[$index] ?? current($types),
                'host'       => $hosts[$index] ?? current($hosts),
                'port'       => $ports[$index] ?? current($ports),
                'user'       => $users[$index] ?? current($users),
                'pwd'        => $pwds[$index] ?? current($pwds),
                'dbname'     => $dbnames[$index] ?? current($dbnames),
                'charset'    => $charsets[$index] ?? current($charsets),
                'persistent' => $persistents[$index] ?? current($persistents),
            ];
        }
        if (!empty($config['charset'])) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$config['charset']}";
        }
        if (isset($config['persistent'])) {
            $options[PDO::ATTR_PERSISTENT] = boolval($config['persistent']);
        }
        // 覆盖options
        $config['options'] = $options;
        return $config;
    }

    /**
     * 连接
     */
    private function connect(bool $force = false)
    {
        $config       = $this->_parseConfig($this->config);
        $options      = $config['options'];
        $this->linkid = md5(serialize($config));
        if (!isset($this->dbhs[$this->linkid]) || $force) {
            $dbn = sprintf(
                '%s:dbname=%s;host=%s;port=%s',
                $config['type'],
                $config['dbname'],
                $config['host'],
                $config['port']
            );
            try {
                Debug::remark('db_connect_begin');
                $this->dbhs[$this->linkid] = new PDO($dbn, $config['user'], $config['pwd'], $options);
                Debug::remark('db_connect_end');
                Log::record(
                    sprintf(
                        "[DB CONNECT] %s [%f sec]",
                        $dbn,
                        Debug::getRangeTime('db_connect_begin', 'db_connect_end')
                    ),
                    Log::INFO
                );
            } catch (PDOException $e) {
                throw $e;
            } catch (Exception $e) {
                throw $e;
            }
        }
        $this->dbh = $this->dbhs[$this->linkid];
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * 保持连接
     */
    public function ping()
    {
        try {
            $this->find("SELECT 1");
        } catch (PDOException $e) {
            $this->connect(true);
        }
        return $this;
    }

    /**
     * 设置缓存参数
     * @param $cache
     * @return $this
     */
    public function cache($cache = null)
    {
        $this->options['cache'] = $cache;
        return $this;
    }

    /**
     * 绑定参数
     * @param $name
     * @param $value
     * @return $this
     */
    public function bind($name = '', $value = '')
    {
        if (!isset($this->options['bind'])) {
            $this->options['bind'] = [];
        }
        if (is_object($name)) {
            $name = get_class_vars($name);
        }
        if (is_array($name)) {
            $this->options['bind'] = array_merge($this->options['bind'], $name);
        } elseif (is_scalar($name)) {
            $this->options['bind'][$name] = $value;
        }
        return $this;
    }

    /**
     * 查询一条记录
     * @param string $sql
     * @return mixed
     * Db::find('select * from table where id = ?', 123);
     * Db::find('select * from table where id = :id', ['id'=>123]);
     * Db::find(function($query){ $query->table('table')->where('id', 1); });
     */
    public function find()
    {
        $args = func_get_args();
        $sql  = array_shift($args);
        // one
        $this->options['one'] = 1;
        if (!empty($args)) {
            $this->options['bind'] = $args;
        }
        return $this->query($sql);
    }

    /**
     * @param string $sql
     * @return mixed
     * Db::select('select * from table where id = ?', 123);
     * Db::select('select * from table where id = :id', ['id'=>123]);
     * Db::select(function($query){ $query->table('table')->where('id', 1); });
     */
    public function select()
    {
        $args = func_get_args();
        $sql  = array_shift($args);
        // one
        $this->options['one'] = 0;
        if (!empty($args)) {
            $this->options['bind'] = $args;
        }
        return $this->query($sql);
    }

    /**
     * 查询SQL
     * @param string $sql
     * @param bool $one
     * @return mixed
     * Db::query('select * from table where id = ?', 123);
     * Db::query('select * from table where id = :id', ['id'=>123]);
     */
    public function query()
    {
        // connect
        $this->connect();
        // reset affectrows/insertid/errno/error
        $this->_affectrows = null;
        $this->_insertid   = null;
        $this->_errno      = null;
        $this->_error      = null;
        // args
        $args = func_get_args();
        $sql  = array_shift($args);
        // 劫持链式操作
        if (
            (empty($sql) || is_bool($sql))
            && $this->_query instanceof Query
        ) {
            // 获取SQL
            if (false === $sql) {
                return Builder::instance()->select($this->_query->getOptions());
            }
            list($sql, $this->_query) = [$this->_query, null];
        }
        // 处理参数绑定
        if (!empty($args) && empty($this->options['bind'])) {
            $this->options['bind'] = $args;
        } elseif (empty($args) && !empty($this->options['bind'])) {
            $args = $this->options['bind'];
        } else {
            $args = [];
        }
        // 解析Query
        if ($sql instanceof Closure) {
            $query = new Query;
            $bind  = $args;
            array_unshift($bind, $query);
            call_user_func_array($sql, $bind);
            $this->_sql = Builder::instance()->select($query->getOptions());
        } elseif ($sql instanceof Query) {
            $this->_sql = Builder::instance()->select($sql->getOptions());
        } elseif (is_string($sql) && !empty($sql)) {
            // 参数绑定
            $params     = self::_parseParams($sql, $this->options['bind'] ?? null);
            $this->_sql = self::_parseSql($sql, $params);
            // 记录参数
            if (!empty($this->options['bind'])) {
                Log::record("[DB BIND] " . preg_replace('/\s+/', ' ', var_export($params, true)), Log::INFO);
            }
        } else {
            throw new Exception("Parse SQL faild", 1);
        }
        // get cache
        if (
            !empty($this->options['cache'])
            && $value = Db::getCache($this->_sql, $this->options['cache'], $this->linkid)
        ) {
            return $value;
        }
        // query
        try {
            $this->stmt = $this->dbh->prepare($this->_sql);
            Debug::remark('sql_begin');
            $this->stmt->execute();
            Debug::remark('sql_end');
            // record sql and runtime
            Log::record(
                sprintf(
                    "[DB QUERY] %s [%f sec]",
                    $this->_sql,
                    Debug::getRangeTime('sql_begin', 'sql_end')
                ), Log::SQL
            );
            $func              = $this->options['one'] ?? 0 ? 'fetch' : 'fetchAll';
            $result            = $this->stmt->$func(PDO::FETCH_ASSOC);
            $this->_affectrows = $this->stmt->rowCount();
        } catch (PDOException $e) {
            $this->options = [];
            $this->_errno  = $e->getCode();
            $this->_error  = $e->getMessage();
            Log::record("SQL:{$this->_sql}, ERROR:" . $e->getMessage(), Log::ERR);
            return false;
        }
        // set cache
        if (!empty($this->options['cache'])) {
            Db::setCache($this->_sql, $result, $this->options['cache'], $this->linkid);
        }
        // reset options
        $this->options = [];
        // return
        return $result;
    }

    /**
     * @param array $data
     * @param $query
     * @return mixed
     */
    public function insert(array $data = [], $query = null, $replace = false)
    {
        if (
            (is_null($query) || is_bool($query))
            && $this->_query instanceof Query
        ) {
            // 获取SQL
            if (false === $query) {
                return Builder::instance()->insert($data, $this->_query->getOptions(), $replace);
            }
            [$query, $this->_query] = [$this->_query, null];
        }
        if (empty($data)) {
            throw new Exception("Insert data is empty", 1);
        }
        if ($query instanceof Closure) {
            $q = new Query;
            call_user_func_array($query, [$q]);
            $sql = Builder::instance()->insert($data, $q->getOptions(), $replace);
        } elseif ($query instanceof Query) {
            $sql = Builder::instance()->insert($data, $query->getOptions(), $replace);
        } else {
            throw new Exception("Invalid query type", 1);
        }
        return $this->execute($sql);
    }

    /**
     * @param array $data
     * @param $query
     * @return mixed
     */
    public function update(array $data = [], $query = null)
    {
        if (
            (is_null($query) || is_bool($query))
            && $this->_query instanceof Query
        ) {
            // 获取SQL
            if (false === $query) {
                return Builder::instance()->update($data, $this->_query->getOptions());
            }
            [$query, $this->_query] = [$this->_query, null];
        }
        if (empty($data)) {
            throw new Exception("Update data is empty", 1);
        }
        if ($query instanceof Closure) {
            $q = new Query;
            call_user_func_array($query, [$q]);
            $sql = Builder::instance()->update($data, $q->getOptions());
        } elseif ($query instanceof Query) {
            $sql = Builder::instance()->update($data, $query->getOptions());
        } else {
            throw new Exception("Invalid query type", 1);
        }
        return $this->execute($sql);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function delete($query = null)
    {
        if (
            (is_null($query) || is_bool($query))
            && $this->_query instanceof Query
        ) {
            // 获取SQL
            if (false === $query) {
                return Builder::instance()->delete($this->_query->getOptions());
            }
            [$query, $this->_query] = [$this->_query, null];
        }
        if ($query instanceof Closure) {
            $q = new Query;
            call_user_func_array($query, [$q]);
            $sql = Builder::instance()->delete($q->getOptions());
        } elseif ($query instanceof Query) {
            $sql = Builder::instance()->delete($query->getOptions());
        } else {
            throw new Exception("Invalid query type", 1);
        }
        return $this->execute($sql);
    }

    /**
     * 统计数量
     * @param string $field 字段名
     * @param bool $distinct 是否去重
     * @return mixed
     */
    public function count(string $field = '1', bool $distinct = false)
    {
        $distinct = $distinct ? 'DISTINCT ' : '';
        return $this->field("COUNT({$distinct}{$field}) AS count")->limit(1)->find()['count'] ?? 0;
    }

    /**
     * 统计和
     * @param string $field 字段名
     * @return mixed
     */
    public function sum(string $field = '')
    {
        return $this->field("SUM({$field}) AS sum")->limit(1)->find()['sum'] ?? 0;
    }

    /**
     * 返回某字段值
     * @param string $field
     * @return mixed
     */
    public function value(string $field = '')
    {
        return $this->field($field)->limit(1)->find()[$field] ?? null;
    }

    /**
     * 返回字段列
     * @return mixed
     * Db::table()->column('id');
     * Db::table()->column('id', 'name');
     * Db::table()->column(['id', 'name']);
     * Db::table()->column('id', true);
     * Db::table()->column('id', 'name', true);
     * Db::table()->column(['id', 'name'], true);
     */
    public function column()
    {
        $argc    = func_num_args();
        $args    = func_get_args();
        $combine = false;
        if (
            isset($args[$argc - 1])
            && is_bool($args[$argc - 1])
        ) {
            $combine = array_pop($args);
        }
        if (
            is_array($args[0])
            && !empty($args[0])
        ) {
            $args = $args[0];
        }
        $args = array_filter($args);
        $argc = count($args);
        if (0 == $argc) {
            $field = '*';
        } else {
            $field = join(', ', $args);
        }
        $rows = $this->field(join(', ', $args))->select();
        if (
            empty($rows) 
            || !$combine 
            || !$argc
        ) {
            return $rows;
        }
        $data = [];
        switch ($argc) {
            case 1:
                $pk = $args[0];
                foreach ($rows as $row) {
                    $data[] = $row[$pk];
                }
                break;
            case 2:
                $pk = $args[0];
                $vk = $args[1];
                foreach ($rows as $row) {
                    $data[$row[$pk]] = $row[$vk];
                }
                break;
            default:
                $pk = $args[0];
                foreach ($rows as $row) {
                    $key = $row[$pk];
                    unset($row[$pk]);
                    $data[$key] = $row;
                }
                break;
        }
        return $data;
    }

    /**
     * 执行SQL
     * @param string $sql
     * @return mixed
     */
    public function execute()
    {
        // connect
        $this->connect();
        // reset affectrows/insertid/errno/error
        $this->_affectrows = null;
        $this->_insertid   = null;
        $this->_errno      = null;
        $this->_error      = null;
        // params
        $args = func_get_args();
        $sql  = array_shift($args);
        if (!empty($args)) {
            $this->options['bind'] = $args;
        }
        // parse params
        $params     = self::_parseParams($sql, $this->options['bind'] ?? null);
        $this->_sql = self::_parseSql($sql, $params);
        // record bind
        if (!empty($this->options['bind'])) {
            Log::record("[DB BIND] " . preg_replace('/\s+/', ' ', var_export($this->options['bind'], true)), Log::INFO);
        }
        // execute
        try {
            $this->stmt = $this->dbh->prepare($this->_sql);
            Debug::remark('sql_begin');
            $result = $this->stmt->execute();
            Debug::remark('sql_end');
            // record sql and runtime
            Log::record(
                sprintf(
                    "[DB EXECUTE] %s [%f sec]",
                    $this->_sql,
                    Debug::getRangeTime('sql_begin', 'sql_end')
                ), Log::SQL
            );
            // 影响行数
            $this->_affectrows = $this->stmt->rowCount();
            $this->_insertid   = $this->dbh->lastInsertId();
        } catch (PDOException $e) {
            $this->options = [];
            $this->_errno  = $e->getCode();
            $this->_error  = $e->getMessage();
            Log::record($e->getMessage(), Log::ERR);
            return false;
        }
        // reset options
        $this->options = [];
        // return
        return $result;
    }

    /**
     * 预解析SQL
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    private static function _parseSql(string $sql = '', array $params = null)
    {
        if (empty($params)) {
            return $sql;
        }
        if (false !== strpos($sql, '?')) {
            $sql = preg_replace_callback('/[\?]/', function ($matches) use (&$params) {
                $var = array_shift($params);
                return self::_parseValue($var);
            }, $sql);
        } elseif (false !== strpos($sql, ':')) {
            $sql = preg_replace_callback('/:(\w+)/', function ($matches) use ($params) {
                $name = $matches[1];
                $var  = $params[$name] ?? null;
                return self::_parseValue($var);
            }, $sql);
        }
        return $sql;
    }

    /**
     * 解析值
     * @param mixed $value
     * @return string
     */
    private static function _parseValue($value = null)
    {
        switch (gettype($value)) {
            case "boolean":
                $value = $value ? 1 : 0;
            case "integer":
            case "double":
                return $value;
            case "string":
                return "'" . addslashes($value) . "'";
            case "object":
                $value = get_object_vars($value);
            case "array":
                $value = array_map(function ($val) {
                    return self::_parseValue($val);
                }, $value);
                return join(',', $value);
            case "resource":
            case "NULL":
            case "unknown type":
                return 'null';
        }
        return 'null';
    }

    /**
     * 解析参数
     * @param array $params
     */
    private static function _parseParams(string $sql = '', array $params = null)
    {
        if (is_null($params)) {
            return null;
        }
        if (false !== strpos($sql, ':')) {
            $params = $params[0] ?? [];
            // id=:id mode
            preg_match_all('/:(\w+)/', $sql, $matches);
            $keys = $matches[1] ?? [];
            $bind = [];
            foreach ($params as $key => $value) {
                if (!in_array($key, $keys)) {
                    continue;
                }
                $bind[$key] = self::_parseValue($value);
            }
            return $bind;
        }
        return $params;
    }

    /**
     * 获取最后生成id
     * @return mixed
     */
    public function getLastInsId()
    {
        return $this->_insertid;
    }

    /**
     * 获取影响行数
     * @return mixed
     */
    public function getAffectedRows()
    {
        return $this->_affectrows;
    }

    /**
     * 获取最后执行的sql
     * @return mixed
     */
    public function getLastSql()
    {
        return $this->_sql;
    }

    /**
     * 返回错误编码
     * @return mixed
     */
    public function getLastErrno()
    {
        return $this->_errno;
    }

    /**
     * 返回错误信息
     * @return mixed
     */
    public function getLastError()
    {
        return $this->_error;
    }

    /**
     * 返回 PDO 对象
     * @return mixed
     */
    public function getPdo()
    {
        return $this->dbh;
    }
}
