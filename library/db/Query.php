<?php
namespace deeka\db;

use Exception;

class Query
{
    private $options   = [];
    private $operators = 'eq|neq|gt|egt|lt|elt|in|notin|between|notbetween|like|notlike|exists|notexists|null|isnull|notnull|isnotnull|exp';

    public static function instance()
    {
        return new self;
    }

    public function __construct()
    {
        //
    }

    public function __call($method, $args)
    {
        // andWhere|orWhere|xorWhere
        if (preg_match("/^(?P<logic>and|or|xor)where$/i", $method, $matches)) {
            if (isset($args[2])) {
                list($field, $operator, $value) = $args;
            } else {
                list($field, $operator, $value) = [$args[0] ?? '', '=', $args[1] ?? ''];
            }
            return $this->where($field, $operator, $value, $matches['logic']);
        } elseif (preg_match("/^where(?P<operator>{$this->operators})$/i", $method, $matches)) {
            if (isset($args[2])) {
                list($field, $value, $logic) = $args;
            } else {
                list($field, $value, $logic) = [$args[0] ?? '', $args[1] ?? '', 'AND'];
            }
            return $this->where($field, $matches['operator'], $value, $logic);
        } elseif (preg_match("/^(?P<logic>and|or|xor)where(?P<operator>{$this->operators})$/i", $method, $matches)) {
            $field = $args[0] ?? '';
            $value = $args[1] ?? '';
            return $this->where($field, $matches['operator'], $value, $matches['logic']);
        } elseif (preg_match('/^(?P<type>left|right|inner|cross)join$/i', $method, $matches)) {
            list($table, $condition) = [$args[0] ?? '', $args[1] ?? ''];
            return $this->join($table, $condition, $matches['type']);
        }
        throw new Exception(__CLASS__ . "::{$method}() is not exists", 1);
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
        // 缺省 operator 和 value
        is_null($operator) && list($operator, $value) = ['EXP', null];
        // 缺省 operator
        is_null($value) && list($value, $operator) = [$operator, '='];
        // 纠正逻辑
        !in_array(strtoupper($logic), ['AND', 'OR', 'XOR']) && $logic = 'AND';
        // 标准化
        $this->options['where'][] = [strtoupper($logic), $field, strtoupper($operator), $value];
        return $this;
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
            list($offset, $length) = [0, $offset];
        }
        $this->options['limit'] = [$offset, $length];
        return $this;
    }

    public function lock($lock = false)
    {
        $this->options['lock'] = $lock;
        return $this;
    }

    public function duplicate(string $duplicate = '')
    {
        $this->options['duplicate'] = $duplicate;
        return $this;
    }

    public function comment(string $comment = '')
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
