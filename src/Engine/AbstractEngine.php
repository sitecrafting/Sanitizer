<?php
/*!
 * Medoo database framework
 * http://medoo.in
 * Version 0.9.8
 * 
 * Copyright 2015, Angel Lai
 * Released under the MIT license
 */
namespace Pegasus\Application\Sanitizer\Engine;

/**
 * Previously medoo, renamed to fit in with the project, copyright notice in place.
 * This also includes a couple of minor modifications.
 *
 * Class AbstractEngine
 * @package Pegasus\Application\Sanitizer\Engine
 */
abstract class AbstractEngine
{
    // General
    protected $_databaseType;

    protected $_charset;

    protected $_databaseName;

    // For MySQL, MariaDB, MSSQL, Sybase, PostgreSQL, Oracle
    protected $_server;

    protected $_userName;

    protected $_password;

    // For SQLite
    protected $_databaseFile;

    // For MySQL or MariaDB with unix_socket
    protected $_socket;

    // Optional
    protected $_port;

    protected $_option = array();

    // Variable
    protected $_logs = array();

    protected $_debugMode = false;

    public function __construct($options = null)
    {
        $dsn = null;

        try {
            $commands = array();

            if (is_string($options) && !empty($options)) {

                if (strtolower($this->_databaseType) == 'sqlite') {
                    $this->_databaseFile = $options;
                } else {
                    $this->_databaseName = $options;
                }
            } elseif (is_array($options)) {

                foreach ($options as $option => $value) {
                    switch($option) {
                        case 'database_type' :
                            $this->_databaseType = $value;
                            break;
                        case 'database_name' :
                        case 'database'      :
                            $this->_databaseName = $value;
                            break;
                        case 'server'   :
                        case 'host'     :
                            $this->_server = $value;
                            break;
                        case 'username' :
                            $this->_userName = $value;
                            break;
                        case 'password' :
                            $this->_password = $value;
                            break;
                        case 'charset' :
                            $this->_charset = $value;
                            break;
                    }
                }
            }

            if (isset($this->_port) && is_int($this->_port * 1)) {
                $port = $this->_port;
            }

            $type = strtolower($this->_databaseType);
            $isPort = isset($port);

            switch ($type)
            {
                case 'mariadb':
                    $type = 'mysql';

                case 'mysql':
                    if ($this->_socket) {
                        $dsn = $type . ':unix_socket=' . $this->_socket . ';dbname=' . $this->_databaseName;
                    } else {
                        $dsn = $type . ':host=' . $this->_server . ($isPort
                                ? ';port=' . $port
                                : '') . ';dbname=' . $this->_databaseName;
                    }

                    // Make MySQL using standard quoted identifier
                    $commands[] = 'SET SQL_MODE=ANSI_QUOTES';
                    break;

                case 'pgsql':
                    $dsn = $type . ':host=' . $this->_server . ($isPort
                            ? ';port=' . $port
                            : '') . ';dbname=' . $this->_databaseName;
                    break;

                case 'sybase':
                    $dsn = 'dblib:host=' . $this->_server . ($isPort
                            ? ':' . $port
                            : '') . ';dbname=' . $this->_databaseName;
                    break;

                case 'oracle':
                    $dbname = $this->_server ?
                        '//' . $this->_server . ($isPort ? ':' . $port : ':1521') . '/' . $this->_databaseName :
                        $this->_databaseName;

                    $dsn = 'oci:dbname=' . $dbname . ($this->_charset ? ';charset=' . $this->_charset : '');
                    break;

                case 'mssql':
                    $dsn = strstr(PHP_OS, 'WIN') ?
                        'sqlsrv:server=' . $this->_server . ($isPort
                            ? ',' . $port
                            : '') . ';database=' . $this->_databaseName :
                        'dblib:host=' . $this->_server . ($isPort
                            ? ':' . $port
                            : '') . ';dbname=' . $this->_databaseName;

                    // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
                    $commands[] = 'SET QUOTED_IDENTIFIER ON';
                    break;

                case 'sqlite':
                    $dsn = $type . ':' . $this->_databaseFile;
                    $this->_userName = null;
                    $this->_password = null;
                    break;
            }

            if (in_array($type, explode(' ', 'mariadb mysql pgsql sybase mssql')) && $this->_charset) {
                $commands[] = "SET NAMES '" . $this->_charset . "'";
            }

            $this->pdo = new \PDO(
                $dsn,
                $this->_userName,
                $this->_password,
                $this->_option
            );

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
        }
        catch (\PDOException $e) {
            if (true == isset($options['skip_init'])) {
                return true;
            }
            throw new \Exception($e->getMessage());
        }
    }

    public function query($query)
    {
        if ($this->_debugMode) {
            echo $query;
            $this->_debugMode = false;

            return false;
        }

        array_push($this->_logs, $query);

        return $this->pdo->query($query);
    }

    public function exec($query)
    {
        if ($this->_debugMode) {
            echo $query;
            $this->_debugMode = false;

            return false;
        }

        array_push($this->_logs, $query);

        return $this->pdo->exec($query);
    }

    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    protected function columnQuote($string)
    {
        return '"' . str_replace('.', '"."', preg_replace('/(^#|\(JSON\))/', '', $string)) . '"';
    }

    protected function columnPush($columns)
    {
        if ($columns == '*') {
            return $columns;
        }

        if (is_string($columns)) {
            $columns = array($columns);
        }

        $stack = array();

        foreach ($columns as $key => $value) {
            preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

            if (isset($match[1], $match[2])) {
                array_push($stack, $this->columnQuote($match[1]) . ' AS ' . $this->columnQuote($match[2]));
            } else {
                array_push($stack, $this->columnQuote($value));
            }
        }

        return implode($stack, ',');
    }

    protected function arrayQuote($array)
    {
        $temp = array();

        foreach ($array as $value) {
            $temp[] = is_int($value) ? $value : $this->pdo->quote($value);
        }

        return implode($temp, ',');
    }

    protected function innerConjunct($data, $conjunctor, $outerConjunctor)
    {
        $haystack = array();

        foreach ($data as $value) {
            $haystack[] = '(' . $this->dataImplode($value, $conjunctor) . ')';
        }

        return implode($outerConjunctor . ' ', $haystack);
    }

    protected function fnQuote($column, $string)
    {
        return (strpos($column, '#') === 0 && preg_match('/^[A-Z0-9\_]*\([^)]*\)$/', $string))
            ? $string
            : $this->quote($string);
    }

    protected function dataImplode($data, $conjunctor, $outerConjunctor = null)
    {
        $wheres = array();

        foreach ($data as $key => $value) {
            $type = gettype($value);

            if (preg_match("/^(AND|OR)\s*#?/i", $key, $relationMatch) && $type == 'array') {
                $wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
                    '(' . $this->dataImplode($value, ' ' . $relationMatch[1]) . ')' :
                    '(' . $this->innerConjunct($value, ' ' . $relationMatch[1], $conjunctor) . ')';
            } else {
                preg_match('/(#?)([\w\.]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\>\<|\!?~)\])?/i', $key, $match);
                $column = $this->columnQuote($match[2]);

                if (isset($match[4])) {
                    $operator = $match[4];

                    if ($operator == '!') {
                        switch ($type)
                        {
                            case 'NULL':
                                $wheres[] = $column . ' IS NOT NULL';
                                break;

                            case 'array':
                                $wheres[] = $column . ' NOT IN (' . $this->arrayQuote($value) . ')';
                                break;

                            case 'integer':
                            case 'double':
                                $wheres[] = $column . ' != ' . $value;
                                break;

                            case 'boolean':
                                $wheres[] = $column . ' != ' . ($value ? '1' : '0');
                                break;

                            case 'string':
                                $wheres[] = $column . ' != ' . $this->fnQuote($key, $value);
                                break;
                        }
                    }

                    if ($operator == '<>' || $operator == '><') {
                        if ($type == 'array') {

                            if ($operator == '><') {
                                $column .= ' NOT';
                            }

                            if (is_numeric($value[0]) && is_numeric($value[1])) {
                                $wheres[] = '(' . $column . ' BETWEEN ' . $value[0] . ' AND ' . $value[1] . ')';
                            } else {
                                $wheres[] = '(' . $column . ' BETWEEN ' . $this->quote($value[0]) .
                                    ' AND ' . $this->quote($value[1]) . ')';
                            }
                        }
                    }

                    if ($operator == '~' || $operator == '!~') {
                        if ($type == 'string') {
                            $value = array($value);
                        }

                        if (!empty($value)) {
                            $likeClauses = array();

                            foreach ($value as $item) {

                                if ($operator == '!~') {
                                    $column .= ' NOT';
                                }

                                if (preg_match('/^(?!%).+(?<!%)$/', $item)) {
                                    $item = '%' . $item . '%';
                                }

                                $likeClauses[] = $column . ' LIKE ' . $this->fnQuote($key, $item);
                            }

                            $wheres[] = implode(' OR ', $likeClauses);
                        }
                    }

                    if (in_array($operator, array('>', '>=', '<', '<='))) {
                        if (is_numeric($value)) {
                            $wheres[] = $column . ' ' . $operator . ' ' . $value;
                        } elseif (strpos($key, '#') === 0) {
                            $wheres[] = $column . ' ' . $operator . ' ' . $this->fnQuote($key, $value);
                        } else {
                            $wheres[] = $column . ' ' . $operator . ' ' . $this->quote($value);
                        }
                    }
                } else {
                    switch ($type) {
                        case 'NULL':
                            $wheres[] = $column . ' IS NULL';
                            break;

                        case 'array':
                            $wheres[] = $column . ' IN (' . $this->arrayQuote($value) . ')';
                            break;

                        case 'integer':
                        case 'double':
                            $wheres[] = $column . ' = ' . $value;
                            break;

                        case 'boolean':
                            $wheres[] = $column . ' = ' . ($value ? '1' : '0');
                            break;

                        case 'string':
                            $wheres[] = $column . ' = ' . $this->fnQuote($key, $value);
                            break;
                    }
                }
            }
        }

        return implode($conjunctor . ' ', $wheres);
    }

    protected function whereClause($where)
    {
        $whereClause = '';

        if (is_array($where)) {
            $whereKeys = array_keys($where);
            $whereAnd = preg_grep("/^AND\s*#?$/i", $whereKeys);
            $whereOr = preg_grep("/^OR\s*#?$/i", $whereKeys);

            $singleCondition = array_diff_key(
                $where, array_flip(
                    explode(' ', 'AND OR GROUP ORDER HAVING LIMIT LIKE MATCH')
                )
            );

            // If we have more elements that 1 in the where clause and it's
            // not explicitly an AND or an OR then default to AND
            if (1 != sizeof($where) && empty($whereAnd) && empty($whereOr)) {
                $whereClause = ' WHERE ' . $this->dataImplode($where, ' AND');
            } elseif ($singleCondition != array()) {             //Fall back to single condition
                $whereClause = ' WHERE ' . $this->dataImplode($singleCondition, ' ');
            }


            if (!empty($whereAnd)) {
                $value = array_values($whereAnd);
                $whereClause = ' WHERE ' . $this->dataImplode($where[ $value[0] ], ' AND');
            }

            if (!empty($whereOr)) {
                $value = array_values($whereOr);
                $whereClause = ' WHERE ' . $this->dataImplode($where[ $value[0] ], ' OR');
            }

            if (isset($where['MATCH'])) {
                $MATCH = $where['MATCH'];

                if (is_array($MATCH) && isset($MATCH['columns'], $MATCH['keyword'])) {
                    $whereClause .= ($whereClause != '' ? ' AND ' : ' WHERE ')
                        . ' MATCH ("' . str_replace('.', '"."', implode($MATCH['columns'], '", "'))
                        . '") AGAINST (' . $this->quote($MATCH['keyword']) . ')';
                }
            }

            if (isset($where['GROUP'])) {
                $whereClause .= ' GROUP BY ' . $this->columnQuote($where['GROUP']);

                if (isset($where['HAVING'])) {
                    $whereClause .= ' HAVING ' . $this->dataImplode($where['HAVING'], ' AND');
                }
            }

            if (isset($where['ORDER'])) {
                $rsort = '/(^[a-zA-Z0-9_\-\.]*)(\s*(DESC|ASC))?/';
                $ORDER = $where['ORDER'];

                if (is_array($ORDER)) {
                    if (isset($ORDER[1]) && is_array($ORDER[1])) {
                        $whereClause .= ' ORDER BY FIELD(' . $this->columnQuote($ORDER[0])
                            . ', ' . $this->arrayQuote($ORDER[1]) . ')';
                    } else {
                        $stack = array();

                        foreach ($ORDER as $column) {
                            preg_match($rsort, $column, $orderMatch);
                            array_push(
                                $stack,
                                '"' .
                                str_replace('.', '"."', $orderMatch[1]) . '"' . (isset($orderMatch[3])
                                    ? ' ' . $orderMatch[3]
                                    : '')
                            );
                        }

                        $whereClause .= ' ORDER BY ' . implode($stack, ',');
                    }
                } else {
                    preg_match($rsort, $ORDER, $orderMatch);

                    $whereClause .= ' ORDER BY "' .
                        str_replace(
                            '.',
                            '"."',
                            $orderMatch[1]
                        ) . '"' . (isset($orderMatch[3])
                                ? ' ' . $orderMatch[3]
                                : ''
                        );
                }
            }

            if (isset($where['LIMIT'])) {
                $LIMIT = $where['LIMIT'];

                if (is_numeric($LIMIT)) {
                    $whereClause .= ' LIMIT ' . $LIMIT;
                }

                if (is_array($LIMIT) && is_numeric($LIMIT[0]) && is_numeric($LIMIT[1])) {
                    $whereClause .= ' LIMIT ' . $LIMIT[0] . ',' . $LIMIT[1];
                }
            }
        } else {
            if ($where != null) {
                $whereClause .= ' ' . $where;
            }
        }

        return $whereClause;
    }

    protected function selectContext($table, $join, &$columns = null, $where = null, $columnFn = null)
    {
        $table = '"' . $table . '"';
        $joinKey = is_array($join) ? array_keys($join) : null;

        if (isset($joinKey[0]) && strpos($joinKey[0], '[') === 0) {
            $tableJoin = array();

            $joinArray = array(
                '>' => 'LEFT',
                '<' => 'RIGHT',
                '<>' => 'FULL',
                '><' => 'INNER'
            );

            foreach ($join as $subTable => $relation) {
                preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $subTable, $match);

                if ($match[2] != '' && $match[3] != '') {
                    if (is_string($relation)) {
                        $relation = 'USING ("' . $relation . '")';
                    }

                    if (is_array($relation)) {
                        // For ['column1', 'column2']
                        if (isset($relation[0])) {
                            $relation = 'USING ("' . implode($relation, '", "') . '")';
                        } else {                        // For ['column1' => 'column2']
                            $relation = 'ON ' . $table . '."' . key($relation) . '" = "'
                                . (isset($match[5])
                                    ? $match[5]
                                    : $match[3])
                                . '"."' . current($relation) . '"';
                        }
                    }

                    $tableJoin[] = $joinArray[ $match[2] ] . ' JOIN "' . $match[3] . '" '
                        . (isset($match[5]) ?  'AS "' . $match[5] . '" ' : '') . $relation;
                }
            }

            $table .= ' ' . implode($tableJoin, ' ');
        } else {
            if (is_null($columns)) {

                if (is_null($where)) {

                    if (is_array($join) && isset($columnFn)) {
                        $where = $join;
                        $columns = null;
                    } else {
                        $where = null;
                        $columns = $join;
                    }

                } else {
                    $where = $join;
                    $columns = null;
                }
            } else {
                $where = $columns;
                $columns = $join;
            }
        }

        if (isset($columnFn)) {

            if ($columnFn == 1) {
                $column = '1';

                if (is_null($where)) {
                    $where = $columns;
                }
            } else {
                if (empty($columns)) {
                    $columns = '*';
                    $where = $join;
                }

                $column = $columnFn . '(' . $this->columnPush($columns) . ')';
            }
        } else {
            $column = $this->columnPush($columns);
        }

        return 'SELECT ' . $column . ' FROM ' . $table . $this->whereClause($where);
    }

    public function select($table, $join, $columns = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $columns, $where));

        return $query ? $query->fetchAll(
            (is_string($columns) && $columns != '*') ? \PDO::FETCH_COLUMN : \PDO::FETCH_ASSOC
        ) : false;
    }

    public function insert($table, $datas)
    {
        $lastId = array();

        // Check indexed or associative array
        if (!isset($datas[0])) {
            $datas = array($datas);
        }

        foreach ($datas as $data) {
            $keys = array_keys($data);
            $values = array();
            $columns = array();

            foreach ($data as $key => $value) {
                array_push($columns, $this->columnQuote($key));

                switch (gettype($value)) {
                    case 'NULL':
                        $values[] = 'NULL';
                        break;

                    case 'array':
                        preg_match("/\(JSON\)\s*([\w]+)/i", $key, $columnMatch);

                        $values[] = isset($columnMatch[0]) ?
                            $this->quote(json_encode($value)) :
                            $this->quote(serialize($value));
                        break;

                    case 'boolean':
                        $values[] = ($value ? '1' : '0');
                        break;

                    case 'integer':
                    case 'double':
                    case 'string':
                        $values[] = $this->fnQuote($key, $value);
                        break;
                }
            }

            $this->exec(
                'INSERT INTO "' . $table . '" (' .
                implode(', ', $columns)
                . ') VALUES (' . implode($values, ', ') . ')'
            );

            $lastId[] = $this->pdo->lastInsertId();
        }

        return count($lastId) > 1 ? $lastId : $lastId[ 0 ];
    }

    public function update($table, $data, $where = null)
    {
        $fields = array();

        foreach ($data as $key => $value) {
            preg_match('/([\w]+)(\[(\+|\-|\*|\/)\])?/i', $key, $match);

            if (isset($match[3])) {
                if (is_numeric($value)) {
                    $fields[] = $this->columnQuote($match[1]) . ' = ' . $this->columnQuote($match[1])
                        . ' ' . $match[3] . ' ' . $value;
                }
            } else {
                $column = $this->columnQuote($key);

                switch (gettype($value)) {
                    case 'NULL':
                        $fields[] = $column . ' = NULL';
                        break;

                    case 'array':
                        preg_match("/\(JSON\)\s*([\w]+)/i", $key, $columnMatch);

                        $fields[] = $column . ' = ' . $this->quote(
                            isset($columnMatch[0]) ? json_encode($value) : serialize($value)
                        );
                        break;

                    case 'boolean':
                        $fields[] = $column . ' = ' . ($value ? '1' : '0');
                        break;

                    case 'integer':
                    case 'double':
                    case 'string':
                        $fields[] = $column . ' = ' . $this->fnQuote($key, $value);
                        break;
                }
            }
        }

        return $this->exec('UPDATE "' . $table . '" SET ' . implode(', ', $fields) . $this->whereClause($where));
    }

    public function delete($table, $where)
    {
        return $this->exec('DELETE FROM "' . $table . '"' . $this->whereClause($where));
    }

    public function truncate($table)
    {
        return $this->exec('TRUNCATE FROM "' . $table . '"');
    }

    public function replace($table, $columns, $search = null, $replace = null, $where = null)
    {
        if (is_array($columns)) {
            $replaceQuery = array();

            foreach ($columns as $column => $replacements) {
                foreach ($replacements as $replaceSearch => $replaceReplacement) {
                    $replaceQuery[] = $column . ' = REPLACE('
                        . $this->columnQuote($column) . ', ' . $this->quote($replaceSearch) . ', '
                        . $this->quote($replaceReplacement) . ')';
                }
            }

            $replaceQuery = implode(', ', $replaceQuery);
            $where = $search;
        } else {
            if (is_array($search)) {
                $replaceQuery = array();

                foreach ($search as $replaceSearch => $replaceReplacement) {
                    $replaceQuery[] = $columns . ' = REPLACE(' . $this->columnQuote($columns)
                        . ', ' . $this->quote($replaceSearch) . ', ' . $this->quote($replaceReplacement) . ')';
                }

                $replaceQuery = implode(', ', $replaceQuery);
                $where = $replace;
            } else {
                $replaceQuery = $columns . ' = REPLACE(' . $this->columnQuote($columns) . ', '
                    . $this->quote($search) . ', ' . $this->quote($replace) . ')';
            }
        }

        return $this->exec('UPDATE "' . $table . '" SET ' . $replaceQuery . $this->whereClause($where));
    }

    public function get($table, $join = null, $column = null, $where = null)
    {
        if (!isset($where)) {
            $where = array();
        }

        $where['LIMIT'] = 1;

        $query = $this->query($this->selectContext($table, $join, $column, $where));

        if ($query) {
            $data = $query->fetchAll(\PDO::FETCH_ASSOC);

            if (isset($data[0])) {
                $column = $where == null ? $join : $column;

                if (is_string($column) && $column != '*') {
                    return $data[ 0 ][ $column ];
                }

                return $data[ 0 ];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function has($table, $join, $where = null)
    {
        $column = null;

        $query = $this->query('SELECT EXISTS(' . $this->selectContext($table, $join, $column, $where, 1) . ')');

        return $query ? $query->fetchColumn() === '1' : false;
    }

    public function count($table, $join = null, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'COUNT'));

        return $query ? 0 + $query->fetchColumn() : false;
    }

    public function max($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'MAX'));

        if ($query) {
            $max = $query->fetchColumn();

            return is_numeric($max) ? $max + 0 : $max;
        }
        return false;
    }

    public function min($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'MIN'));

        if ($query) {
            $min = $query->fetchColumn();

            return is_numeric($min) ? $min + 0 : $min;
        }

        return false;
    }

    public function avg($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'AVG'));

        return $query ? 0 + $query->fetchColumn() : false;
    }

    public function sum($table, $join, $column = null, $where = null)
    {
        $query = $this->query($this->selectContext($table, $join, $column, $where, 'SUM'));

        return $query ? 0 + $query->fetchColumn() : false;
    }

    public function debug()
    {
        $this->_debugMode = true;

        return $this;
    }

    public function error()
    {
        return $this->pdo->errorInfo();
    }

    public function last_query()
    {
        return end($this->_logs);
    }

    public function log()
    {
        return $this->_logs;
    }

    public function info()
    {
        $output = array(
            'server' => 'SERVER_INFO',
            'driver' => 'DRIVER_NAME',
            'client' => 'CLIENT_VERSION',
            'version' => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS'
        );

        foreach ($output as $key => $value) {
            $output[ $key ] = $this->pdo->getAttribute(constant('\PDO::ATTR_' . $value));
        }

        return $output;
    }
}
