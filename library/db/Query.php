<?php
namespace deeka\db;

use Exception;

class Query
{
    private $options      = [];
    private $expMaps      = ['EQ' => '=', 'NEQ' => '<>', 'GT' => '>', 'EGT' => '>=', 'LT' => '<', 'ELT' => '<='];
    private $operatorMaps = [
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
        //
    }

    public function distinct(bool $distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    public function field($field = '')
    {
        $this->options['field'] = $field;
        return $this;
    }

    public function count(string $field = '1', string $alias = null)
    {
        $field = "COUNT({$field})";
        if (!is_null($alias)) {
            $field .= " AS {$alias}";
        }
        return $this->field($field);
    }

    public function sum(string $field = '', string $alias = null)
    {
        $field = "SUM({$field})";
        if (!is_null($alias)) {
            $field .= " AS {$alias}";
        }
        return $this->field($field);
    }

    public function table(string $table = '')
    {
        $this->options['table'] = $table;
        return $this;
    }

    public function force($force)
    {
        $this->options['force'] = $force;
        return $this;
    }

    public function using($using)
    {
        $this->options['using'] = $using;
        return $this;
    }

    public function join(string $table = '', string $condition = '', string $type = 'LEFT')
    {
        if (!isset($this->options['join'])) {
            $this->options['join'] = [];
        }
        $type = strtoupper($type);
        if (!in_array($type, ['LEFT', 'RIGHT', 'INNER', 'OUTER', 'CROSS'])) {
            $type = 'LEFT';
        }
        $this->options['join'][] = [$type, $table, $condition];
        return $this;
    }

    public function leftJoin(string $table = '', string $condition = '')
    {
        return $this->join($table, $condition, 'LEFT');
    }

    public function rightJoin(string $table = '', string $condition = '')
    {
        return $this->join($table, $condition, 'RIGHT');
    }

    public function innerJoin(string $table = '', string $condition = '')
    {
        return $this->join($table, $condition, 'INNER');
    }

    public function outerJoin(string $table = '', string $condition = '')
    {
        return $this->join($table, $condition, 'OUTER');
    }

    public function crossJoin(string $table = '', string $condition = '')
    {
        return $this->join($table, $condition, 'CROSS');
    }

    public function where($field = '', $operator = null, $value = null, string $logic = 'AND')
    {
        if (!isset($this->options['where'])) {
            $this->options['where'] = [];
        }
        if ($field instanceof \Closure) {
            $instance = new Query;
            call_user_func_array($field, [ & $instance]);
            $this->options['where'][] = [$logic, $instance->getOption('where')];
            return $this;
        } elseif ($field instanceof Query) {
            $this->options['where'][] = [$logic, $field->getOption('where')];
            return $this;
        }
        if (empty($field)) {
            throw new Exception("Field can not be empty.", 1);
        }
        if (is_null($operator)) {
            $operator = 'exp';
        }
        if (in_array(strtoupper($operator), array_keys($this->expMaps))) {
            $operator = $this->expMaps[strtoupper($operator)];
        }
        if (!in_array(strtoupper($operator), array_keys($this->operatorMaps)) ) {
            is_null($value) && [$value, $operator] = [$operator, '='];
        } else {
            $operator = $this->operatorMaps[strtoupper($operator)];
        }
        if (!in_array(strtoupper($logic), ['AND', 'OR', 'XOR'])) {
            $logic = 'AND';
        }
        $this->options['where'][] = [strtoupper($logic), $field, strtoupper($operator), $value];
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

    public function whereIn(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'IN', $value, $logic);
    }

    public function whereNotIn(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'NOT IN', $value, $logic);
    }

    public function whereLike(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'LIKE', $value, $logic);
    }

    public function whereNotLike(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'NOT LIKE', $value, $logic);
    }

    public function whereExists(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'EXISTS', $value, $logic);
    }

    public function whereNotExists(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'NOT EXISTS', $value, $logic);
    }

    public function whereBetween(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'BETWEEN', $value, $logic);
    }

    public function whereNotBetween(string $field = '', $value = '', string $logic = 'AND')
    {
        return $this->where($field, 'NOT BETWEEN', $value, $logic);
    }

    public function whereNull(string $field = '', string $logic = 'AND')
    {
        return $this->where($field, 'IS NULL', null, $logic);
    }

    public function whereNotNull(string $field = '', string $logic = 'AND')
    {
        return $this->where($field, 'IS NOT NULL', null, $logic);
    }

    public function whereExp(string $file = '', string $logic = 'AND')
    {
        return $this->where($field, 'exp', null, $logic);
    }

    public function groupBy(string $group = '')
    {
        $this->options['group'] = $group;
        return $this;
    }

    public function having(string $having = '')
    {
        $this->options['having'] = $having;
        return $this;
    }

    public function orderBy(string $field = '', string $order = 'ASC')
    {
        if (!isset($this->options['order'])) {
            $this->options['order'] = [];
        }
        $this->options['order'][] = [$field, $order];
        return $this;
    }

    public function limit(int $offset = 0, int $length = null)
    {
        if (is_null($length)) {
            [$offset, $length] = [0, $length];
        }
        $this->options['limit'] = [$offset, $length];
        return $this;
    }

    public function lock($lock = false)
    {
        $this->options['lock'] = $lock;
        return $this;
    }

    public function comment($comment = '')
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    public function getOption(string $name = '')
    {
        if ('' == $name) {
            return $this->options;
        }
        return $this->options[$name] ?? null;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
