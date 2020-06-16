<?php
namespace deeka\db\builder;

use deeka\db\BuilderInterface;

class Mysql implements BuilderInterface
{
    protected $options      = [];
    protected $selectSql    = 'SELECT%DISTINCT%%FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %LOCK%%COMMENT%';
    protected $insertSql    = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%)%DUPLICATE%%COMMENT%';
    protected $insertAllSql = '%INSERT% INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';
    protected $updateSql    = 'UPDATE %TABLE% SET %SET%%JOIN%%WHERE%%ORDER%%LIMIT%%LOCK%%COMMENT%';
    protected $deleteSql    = 'DELETE FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER%%LIMIT%%LOCK%%COMMENT%';
    protected $alias        = [
        'EQ'         => '=',
        'NEQ'        => '<>',
        'GT'         => '>',
        'EGT'        => '>=',
        'LT'         => '<',
        'ELT'        => '<=',
        'ISNULL'     => 'IS NULL',
        'NULL'       => 'IS NULL',
        'NOTNULL'    => 'IS NOT NULL',
        'ISNOTNULL'  => 'IS NOT NULL',
        'NOTBETWEEN' => 'NOT BETWEEN',
    ];

    public static function instance()
    {
        return new self;
    }

    public function __construct()
    {
    }

    public function selectInsert($fields = '', $table = '', array $options = [])
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        $sql = 'INSERT INTO ' . $this->parseTable($table) . ' (' . join(',', $fields) . ') ' . $this->select($options);
        return $sql;
    }

    public function select(array $options = [])
    {
        $sql = str_replace(
            [
                '%TABLE%',
                '%DISTINCT%',
                '%FIELD%',
                '%JOIN%',
                '%WHERE%',
                '%GROUP%',
                '%HAVING%',
                '%ORDER%',
                '%LIMIT%',
                '%LOCK%',
                '%COMMENT%',
                '%FORCE%',
            ],
            [
                $this->parseTable($options['table'] ?? ''),
                $this->parseDistinct($options['distinct'] ?? ''),
                $this->parseField($options['field'] ?? ''),
                $this->parseJoin($options['join'] ?? ''),
                $this->parseWhere($options['where'] ?? ''),
                $this->parseGroup($options['group'] ?? ''),
                $this->parseHaving($options['having'] ?? ''),
                $this->parseOrder($options['order'] ?? ''),
                $this->parseLimit($options['limit'] ?? ''),
                $this->parseLock($options['lock'] ?? false),
                $this->parseComment($options['comment'] ?? ''),
                $this->parseForce($options['force'] ?? ''),
            ], $this->selectSql);
        return $sql;
    }

    public function insertAll(array $data, array $options = [], $replace = false)
    {
        $data = $this->parseData($data);
        if (empty($data)) {
            return 0;
        }
        $fields = array_keys($data[0]);
        $values = [];
        foreach ($data as $rows) {
            $_row = [];
            foreach ($fields as $field) {
                $_row[] = $this->parseValue($rows[$field]) ?? '';
            }
            $values[] = '(' . join(', ', $_row) . ')';
        }
        $sql = str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($options['table'] ?? ''),
                join(', ', $fields),
                join(', ', $values),
                $this->parseComment($options['comment'] ?? ''),
            ], $this->insertAllSql);
        return $sql;
    }

    public function insert(array $data = [], array $options = [], $replace = false)
    {
        $data = $this->parseData($data, $options);
        if (empty($data)) {
            return 0;
        }
        $fields = array_keys($data);
        $values = array_values($data);
        $sql    = str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%DUPLICATE%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($options['table'] ?? ''),
                join(', ', $fields),
                join(', ', $values),
                $this->parseDuplicate($options['duplicate'] ?? ''),
                $this->parseComment($options['comment'] ?? ''),
            ], $this->insertSql);
        return $sql;
    }

    public function update(array $data = [], array $options = [])
    {
        $table = $this->parseTable($options['table']);
        $data  = $this->parseData($data, $options);
        if (empty($data)) {
            return '';
        }
        $set = [];
        foreach ($data as $key => $val) {
            $set[] = $key . ' = ' . $val;
        }
        $sql = str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table'] ?? ''),
                implode(',', $set),
                $this->parseJoin($options['join'] ?? ''),
                $this->parseWhere($options['where'] ?? ''),
                $this->parseOrder($options['order'] ?? ''),
                $this->parseLimit($options['limit'] ?? ''),
                $this->parseLock($options['lock'] ?? false),
                $this->parseComment($options['comment'] ?? ''),
            ], $this->updateSql);
        return $sql;
    }

    public function delete(array $options = [])
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table'] ?? ''),
                !empty($options['using'] ?? '') ? ' USING ' . $this->parseTable($options['using'] ?? '') . ' ' : '',
                $this->parseJoin($options['join'] ?? '', $options),
                $this->parseWhere($options['where'] ?? '', $options),
                $this->parseOrder($options['order'] ?? '', $options),
                $this->parseLimit($options['limit'] ?? ''),
                $this->parseLock($options['lock'] ?? ''),
                $this->parseComment($options['comment'] ?? ''),
            ], $this->deleteSql);
        return $sql;
    }

    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT' : '';
    }

    public function parseField($field = '')
    {
        is_object($field) && $field = get_object_vars($field);
        is_array($field) && $field  = join(', ', $field);
        $field                      = $field ?: '*';
        return " {$field}";
    }

    public function parseTable($table)
    {
        return $table;
    }

    public function parseJoin($join)
    {
        if (empty($join)) {
            return '';
        }
        $joinStr = '';
        foreach ($join as $item) {
            list($type, $table, $condition) = $item;
            $joinStr .= " {$type} JOIN {$table} ON {$condition}";
        }
        return $joinStr;
    }

    public function parseWhere($options)
    {
        return !empty($options) ? " WHERE {$this->buildWhere($options)}" : '';
    }

    public function buildWhere($options)
    {
        $where = '';
        if (empty($options)) {
            return $where;
        }
        foreach ($options as $options) {
            list($logic, $field, $operator, $value) = [$options[0] ?? '', $options[1] ?? '', $options[2] ?? '', $options[3] ?? ''];
            if (empty($field)) {
                continue;
            }
            if (is_array($field)) {
                $where .= (empty($where) ? '' : " {$logic} ") . "({$this->buildWhere($field, true)})";
            } else {
                $where .= (empty($where) ? '' : " {$logic} ") . $this->parseWhereItem($field, $operator, $value);
            }
        }
        return $where;
    }

    public function parseWhereItem($field = '', $operator = null, $value = null)
    {
        if (in_array($operator, array_keys($this->alias))) {
            $operator = $this->alias[$operator];
        }
        switch ($operator) {
            case '>':
            case '>=':
            case '<':
            case '<=':
            case '=':
            case '<>':
            case '!=':
                $value = $this->parseValue($value);
                $where = "{$field} {$operator} {$value}";
                break;
            case 'IN':
            case 'NOT IN':
                is_object($value) && $value = get_defined_vars($value);
                is_string($value) && $value = explode(',', $value);

                $value = $this->parseValue($value);
                $where = "{$field} {$operator} ({$value})";
                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                is_object($value) && $value = get_defined_vars($value);
                is_string($value) && $value = explode(',', $value);

                $value[0] = $this->parseValue($value[0]);
                $value[1] = $this->parseValue($value[1]);
                $where    = "{$field} {$operator} {$value[0]} AND {$value[1]}";
                break;
            case 'LIKE':
            case 'NOT LIKE':
                $value = $this->parseValue($value);
                $where = "{$field} {$operator} {$value}";
                break;
            case 'EXISTS':
            case 'NOT EXISTS':
                $where = "{$field} {$operator} {$value}";
                break;
            case 'IS NULL';
            case 'IS NOT NULL':
                $where = "{$field} {$operator}";
                break;
            case 'EXP':
            default:
                $where = "{$field}";
                break;
        }
        return $where;
    }

    public function parseOrder($order)
    {
        if (empty($order)) {
            return '';
        }
        $items = [];
        foreach ($order as $item) {
            list($field, $order) = $item;
            $items[]             = "{$field} {$order}";
        }
        return " ORDER BY " . join(', ', $items);
    }

    public function parseGroup($group)
    {
        return !empty($group) ? " GROUP BY {$group}" : '';
    }

    public function parseHaving($having)
    {
        return !empty($having) ? " HAVING {$having}" : '';
    }

    public function parseLimit($limit)
    {
        if (empty($limit)) {
            return '';
        }
        if (is_array($limit)) {
            list($offset, $limit) = $limit;
            if (empty($limit)) {
                return " LIMIT {$offset}";
            } else {
                return " LIMIT {$offset}, {$limit}";
            }
        } else {
            return " LIMIT {$limit}";
        }
    }

    public function parseDuplicate($duplicate)
    {
        return !empty($duplicate) ? " ON DUPLICATE KEY UPDATE {$duplicate}" : '';
    }

    public function parseComment($comment)
    {
        return !empty($comment) ? " /* {$comment} */" : '';
    }

    protected function parseForce($index)
    {
        if (empty($index)) {
            return '';
        }
        if (is_array($index)) {
            $index = join(",", $index);
        }
        return sprintf(" FORCE INDEX ( %s ) ", $index);
    }

    protected function parseLock($lock = false)
    {
        if (is_bool($lock)) {
            return $lock ? ' FOR UPDATE ' : '';
        } elseif (is_string($lock)) {
            return ' ' . trim($lock) . ' ';
        }
    }

    public function parseData($data)
    {
        return $data;
    }

    protected function parseKey($key)
    {
        return $key;
    }

    public function parseValue($value = null)
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
                    return $this->parseValue($val);
                }, $value);
                return join(',', $value);
            case "resource":
            case "NULL":
            case "unknown type":
                return 'null';
        }
        return 'null';
    }
}
