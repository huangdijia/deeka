<?php
namespace deeka\db;

class Query
{
    private $options = [];

    public static function instance()
    {
        return new self;
    }

    public function __construct()
    {
        //
    }

    public function field($field = '')
    {
        is_object($field) && $field = get_object_vars($field);
        is_array($field) && $field = join(',', $field);
        $this->options['field'] = $field;
        return $this;
    }

    public function count($field = '1', $alias = null)
    {
        $field = "COUNT({$field})";
        if (!is_null($alias)) {
            $field .= " AS {$alias}";
        }
        return $this->field($field);
    }

    public function sum($field = '', $alias = null)
    {
        $field = "SUM({$field})";
        if (!is_null($alias)) {
            $field .= " AS {$alias}";
        }
        return $this->field($field);
    }

    public function db($dbname = '')
    {
        $this->options['dbname'] = $dbname;
        return $this;
    }

    public function table($table = '')
    {
        $this->options['table'] = $table;
        return $this;
    }

    public function index($index = '', $type = '')
    {
        $this->options['index'] = [$type, $index];
        return $this;
    }

    public function forceIndex($index = '')
    {
        $this->options['index'] = ['FORCE', $index];
    }

    public function using($using = '')
    {
        $this->options['using'] = $using;
        return $this;
    }

    public function join($table = '', $condition = '', $type = 'LEFT')
    {
        if (!isset($this->options['join'])) {
            $this->options['join'] = [];
        }
        $this->options['join'][] = [$type, $talbe, $condition];
        return $this;
    }

    public function leftJoin($table = '', $condition = '')
    {
        return $this->join($table, $condition, 'LEFT');
    }

    public function rightJoin($talbe = '', $condition = '')
    {
        return $this->join($table, $condition, 'RIGHT');
    }

    public function innerJoin($table = '', $condition = '')
    {
        return $this->join($talbe, $condition, 'INNER');
    }

    public function outerJoin($table = '', $condition = '')
    {
        return $this->join($table, $condition, 'OUTER');
    }

    public function crossJoin($table = '', $condition = '')
    {
        return $this->join($table, $condition, 'CROSS');
    }

    public function where($field = '', $operator = null, $value = null, $logic = 'AND')
    {
        if (!isset($this->options['where'])) {
            $this->options['where'] = [];
        }
        if (is_null($operator)) {
            $operator = 'exp';
        }
        if (is_null($value)) {
            [$operator, $value] = ['=', $operator];
        }
        if (!in_array(strtoupper($logic), ['AND', 'OR', 'XOR'])) {
            $logic = 'AND';
        }
        $this->options['where'][] = [$logic, $field, $operator, $value];
        return $this;
    }

    public function orWhere($field = '', $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'OR');
    }

    public function xorWhere($field = '', $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'XOR');
    }

    public function andWhere($field = '', $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'AND');
    }

    public function whereIn($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'IN', $value, $logic);
    }

    public function whereNotIn($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'NOT IN', $value, $logic);
    }

    public function whereLike($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'LIKE', $value, $logic);
    }

    public function whereNotLike($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'NOT LIKE', $value, $logic);
    }

    public function whereExists($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'EXISTS', $value, $logic);
    }

    public function whereNotExists($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'NOT EXISTS', $value, $logic);
    }

    public function whereBetween($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'BETWEEN', $value, $logic);
    }

    public function whereNotBetween($field = '', $value = '', $logic = 'AND')
    {
        return $this->where($field, 'NOT BETWEEN', $value, $logic);
    }

    public function whereNull($field = '', $logic = 'AND')
    {
        return $this->where($field, 'IS NULL', null, $logic);
    }

    public function whereNotNull($field = '', $logic = 'AND')
    {
        return $this->where($field, 'IS NOT NULL', null, $logic);
    }

    public function whereExp($file = '', $logic = 'AND')
    {
        return $this->where($field, 'exp', null, $logic);
    }

    public function groupBy($group = '')
    {
        $this->options['group'] = $group;
    }

    public function having($having = '')
    {
        $this->options['having'] = $having;
    }

    public function orderBy($field = '', $order = 'ASC')
    {
        $this->options['order'] = $order;
    }

    public function limit($offset = 0, $length = null)
    {
        $this->options['limit'] = [$offset, $length];
    }
}