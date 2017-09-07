<?php
namespace deeka\db\driver;

use deeka\Cache;
use deeka\Db;
use deeka\Debug;
use deeka\Log;
use Exception;
use PDO;
use PDOException;

class Mysql
{
    /**
     * @var mixed
     */
    private $linkid = null;
    /**
     * @var mixed
     */
    private $dbh = null;
    /**
     * @var mixed
     */
    private $stmt = null;
    /**
     * @var mixed
     */
    private $_sql = null;
    /**
     * @var array
     */
    private $dbhs = [];
    /**
     * @var array
     */
    private $config = [];
    /**
     * @var array
     */
    private $options = [];
    /**
     * @var mixed
     */
    private $_error = null;
    /**
     * @var mixed
     */
    private $_errno = null;
    /**
     * @var int
     */
    private $_affectrows = 0;
    /**
     * @var int
     */
    private $_insertid = 0;

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
            $this->selectOne("SELECT 1");
        } catch (PDOException $e) {
            $this->connect(true);
        }
        return $this;
    }

    /**
     * 设置缓存参数
     * @param $cache
     * @return mixed
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
     * @return mixed
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
     * Db::selectOne('select * from table where id = ?', 123);
     * Db::selectOne('select * from table where id = :id', ['id'=>123]);
     */
    public function selectOne()
    {
        $args = func_get_args();
        $sql  = array_shift($args);
        $this->options['one'] = 1;
        if (!empty($args)) {
            $this->options['bind'] = $args;
        }
        return $this->query($sql, true);
    }

    /**
     * @param string $sql
     * @return mixed
     * Db::selectAll('select * from table where id = ?', 123);
     * Db::selectAll('select * from table where id = :id', ['id'=>123]);
     */
    public function selectAll()
    {
        $args = func_get_args();
        $sql  = array_shift($args);
        $this->options['one'] = 0;
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
     */
    public function select()
    {
        $args = func_get_args();
        $sql  = array_shift($args);
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
    public function query(string $sql = '')
    {
        // connect
        $this->connect();
        // reset affectrows/insertid/errno/error
        $this->_affectrows = null;
        $this->_insertid   = null;
        $this->_errno      = null;
        $this->_error      = null;
        // parse params
        $params     = self::_parseParams($sql, $this->options['bind'] ?? null);
        $this->_sql = self::_parseSql($sql, $params);
        // record bind
        if (!empty($this->options['bind'])) {
            Log::record("[DB BIND] " . preg_replace('/\s+/', ' ', var_export($params, true)), Log::INFO);
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
            // $this->stmt = $this->dbh->prepare($sql);
            $this->stmt = $this->dbh->prepare($this->_sql);
            Debug::remark('sql_begin');
            // $this->stmt->execute($params);
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
            $func              = $this->options['one'] ? 'fetch' : 'fetchAll';
            $result            = $this->stmt->$func(PDO::FETCH_ASSOC);
            $this->_affectrows = $this->stmt->rowCount();
        } catch (PDOException $e) {
            $this->options = [];
            $this->_errno  = $e->getCode();
            $this->_error  = $e->getMessage();
            Log::record($e->getMessage(), Log::ERR);
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
            // $this->stmt = $this->dbh->prepare($sql);
            $this->stmt = $this->dbh->prepare($this->_sql);
            Debug::remark('sql_begin');
            // $result = $this->stmt->execute($params);
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
}