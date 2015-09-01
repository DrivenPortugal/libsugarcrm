<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Rémi Sauvat
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license GNU General Public License v2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM\Database;

use PDO;

/**
 * Simple SQL Query factory for standard request
 */
class QueryFactory
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function createInsertQuery($table, $data)
    {
        $sql = 'INSERT INTO ' . $table;
        $sql .= ' (' . implode(', ', array_keys($data)) . ')';
        $sql .= ' VALUES';
        $params = array();
        foreach ($data as $key => $value) {
            $params[":$key"] = $value;
        }
        $sql .= ' (' . implode(', ', array_keys($params)) . ')';
        return new Query($this->getPdo(), $sql, $params);
    }

    public function createDeleteQuery($table, $id)
    {
        $sql = 'DELETE FROM ' . $table;
        $sql .= ' WHERE id = :id';
        return new Query($this->getPdo(), $sql, array(':id' => $id));
    }

    public function createUpdateQuery($table, $id, $data)
    {
        $sql = 'UPDATE ' . $table;
        $sets = array();
        $params = array();

        foreach ($data as $key => $value) {
            $sets[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $sql .= ' SET ' . implode(', ', $sets);
        $sql .= ' WHERE id = :primary_id';
        $params[':primary_id'] = $id;
        return new Query($this->getPdo(), $sql, $params);
    }
}
