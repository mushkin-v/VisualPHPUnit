<?php
/**
 * VisualPHPUnit
 *
 * PHP Version 5.3<
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright 2011-2015 VisualPHPUnit
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 * @link      https://github.com/VisualPHPUnit/VisualPHPUnit VisualPHPUnit
 */
namespace app\lib;

use \PDO;
use \PDOException;

/**
 * PDOMySQL
 *
 * Class for managing PDO operations
 *
 * @author Nick Sinopoli <NSinopoli@gmail.com>
 */
class PDOMySQL
{

    /**
     * Number of rows affected by MySQL query.
     *
     * @var int
     */
    protected $affectedRows = 0;

    /**
     * The db handle.
     *
     * @var object
     */
    protected $dbh;

    /**
     * The error messages generated by PDOExceptions.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * The result set associated with a prepared statement.
     *
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * Returns the number of rows affected by the last DELETE,
     * INSERT, or UPDATE query.
     *
     * @return integer
     */
    public function affectedRows()
    {
        return $this->affectedRows;
    }

    /**
     * Closes the connection.
     *
     * @return boolean
     */
    public function close()
    {
        $this->dbh = null;
        return true;
    }

    /**
     * Connects and selects database.
     *
     * @param array $options
     *            Contains the connection information. Takes the
     *            following options:
     *            'database' - The name of the database.
     *            'host' - The database host.
     *            'port' - The database port.
     *            'username' - The database username.
     *            'password' - The database password.
     * @return boolean
     */
    public function connect($options = array())
    {
        $dsn = "mysql:host={$options['host']};port={$options['port']}" . ";dbname={$options['database']}";
        try {
            $this->dbh = new PDO($dsn, $options['username'], $options['password']);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Fetches the next row from the result set in memory (i.e., the one
     * that was created after running query()).
     *
     * @param string $fetch_style
     *            Controls how the rows will be returned.
     * @param obj $obj
     *            The object to be fetched into if
     *            $fetch_style is set to 'into'.
     * @return mixed
     */
    public function fetch($fetch_style = null, $obj = null)
    {
        $this->setFetchMode($fetch_style, $obj);
        $row = $this->statement->fetch();
        $this->statement->closeCursor();
        return $row;
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param string $fetch_style
     *            Controls how the rows will be returned.
     * @return mixed
     */
    public function fetchAll($fetch_style = null)
    {
        $this->setFetchMode($fetch_style);
        $rows = $this->statement->fetchAll();
        $this->statement->closeCursor();
        return $rows;
    }

    /**
     * Returns a single column from the next row of a result set or false
     * if there are no more rows.
     *
     * @param integer $column_number
     *            Zero-index number of the column to
     *            retrieve from the row.
     * @return mixed
     */
    public function fetchColumn($column_number = 0)
    {
        $column = $this->statement->fetchColumn($column_number);
        $this->statement->closeCursor();
        return $column;
    }

    /**
     * Returns the errors generated by PDOExceptions.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Inserts a record into the database.
     *
     * @param string $table
     *            The table containing the record to be inserted.
     * @param array $data
     *            An array containing the data to be inserted.
     *            Format should be as follows:
     *            array('column_name' => 'column_value');
     * @return bool
     */
    public function insert($table, $data)
    {
        $sql = "INSERT INTO {$table} ";
        
        $key_names = array_keys($data);
        $fields = implode(', ', $key_names);
        $values = ':' . implode(', :', $key_names);
        
        $sql .= "({$fields}) VALUES ({$values})";
        
        $statement = $this->dbh->prepare($sql);
        
        try {
            $statement->execute($data);
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
        
        $this->affectedRows = $statement->rowCount();
        return true;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @return integer
     */
    public function insertId()
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * Executes SQL query.
     *
     * @param string $sql
     *            The SQL query to be executed.
     * @param array $parameters
     *            An array containing the parameters to be
     *            bound.
     * @return boolean
     */
    public function query($sql, $parameters = array())
    {
        $statement = $this->dbh->prepare($sql);
        
        foreach ($parameters as $index => $parameter) {
            $statement->bindValue($index + 1, $parameter);
        }
        
        try {
            $statement->execute();
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
        
        $this->affectedRows = $statement->rowCount();
        $this->statement = $statement;
        return true;
    }

    /**
     * Sets the fetch mode.
     *
     * @param string $fetch_style
     *            Controls how the rows will be returned.
     * @param obj $obj
     *            The object to be fetched into for use with
     *            FETCH_INTO.
     * @return integer
     */
    protected function setFetchMode($fetch_style, $obj = null)
    {
        switch ($fetch_style) {
            case 'assoc':
                $this->statement->setFetchMode(PDO::FETCH_ASSOC);
                break;
            case 'into':
                $this->statement->setFetchMode(PDO::FETCH_INTO, $obj);
                break;
            default:
                $this->statement->setFetchMode(PDO::FETCH_ASSOC);
                break;
        }
    }

    /**
     * Updates a record in the database.
     *
     * @param string $table
     *            The table containing the record to be inserted.
     * @param array $data
     *            An array containing the data to be inserted.
     *            Format should be as follows:
     *            array('column_name' => 'column_value');
     * @param array $where
     *            The WHERE clause of the SQL query.
     * @return boolean
     */
    public function update($table, $data, $where = null)
    {
        $sql = "UPDATE {$table} SET ";
        
        $key_names = array_keys($data);
        foreach ($key_names as $name) {
            $sql .= "{$name}=:{$name}, ";
        }
        
        $sql = rtrim($sql, ', ');
        
        if (! is_null($where)) {
            $sql .= ' WHERE ';
            foreach ($where as $name => $val) {
                $sql .= "{$name}=:{$name}_where, ";
                $data["{$name}_where"] = $val;
            }
        }
        $statement = $this->dbh->prepare($sql);
        
        try {
            $statement->execute($data);
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
        
        $this->affectedRows = $statement->rowCount();
        return true;
    }

    /**
     * Inserts or updates (if exists) a record in the database.
     *
     * @param string $table
     *            The table containing the record to be inserted.
     * @param array $data
     *            An array containing the data to be inserted.
     *            Format should be as follows:
     *            array('column_name' => 'column_value');
     * @return boolean
     */
    public function upsert($table, $data)
    {
        $sql = "INSERT INTO {$table}";
        
        $key_names = array_keys($data);
        $fields = implode(', ', $key_names);
        $values = ':' . implode(', :', $key_names);
        
        $sql .= "({$fields}) VALUES ({$values}) ON DUPLICATE KEY UPDATE ";
        
        foreach ($key_names as $name) {
            $sql .= "{$name}=:{$name}, ";
        }
        
        $sql = rtrim($sql, ', ');
        $statement = $this->dbh->prepare($sql);
        
        try {
            $statement->execute($data);
        } catch (PDOException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
        
        $this->affectedRows = $statement->rowCount();
        return true;
    }
}
