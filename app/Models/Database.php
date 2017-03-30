<?php
namespace App\Models;

/**
 * PDO database wrapper
 *
 * @author Michal Carson <michal.carson@carsonsoftwareengineering.com>
 * @copyright (c) 2017, Carson Software Engineering
 *
 */
use PDO;
use PDOStatement;
use PDOException;

class Database
{
    /**
     * @var PDO
     */
    protected $conn; //db connection
    /**
     * @var PDOStatement
     */
    protected $statement = false;
    /**
     * file pointer for sql log
     * define('SQL_LOG', 'filename.sql') to enable sql logging
     * @var resource
     */
    protected $log_file = null;
    /**
     * singleton instance
     */
    protected static $instance = null;
    /**
     * @var PDOException
     */
    protected $exception = null;
    protected $time_zone = '-6:00';
    protected $debug = false; //set to true to see SQL queries

    public function __construct()
    {
        if (defined('LOG_SQL')) {
            $this->log_file = fopen(LOG_SQL, 'a');
        }
    }

    public function __destruct()
    {
        if (is_resource($this->log_file)) {
            fclose($this->log_file);
        }
    }

    /**
     * return singleton instance of the model     *
     */
    public static function getInstance()
    {
        if ( ! self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * set up the db connection. expects constants to be set for
     * DBSTRING: the database uri
     * DBUSER: the user name for accessing the database
     * DBPASS: password for this user
     */
    protected function initConnection()
    {
        try {
            $this->conn = new PDO(DBSTRING, DBUSER, DBPASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (PDOException $pe) {
            die('Unable to connect to storage: ' . $pe->getMessage());
        }
        $this->query("set time_zone='{$this->time_zone}'");
    }

    /**
     * return the current database connection
     *
     * @return PDO
     */
    public function getConnection()
    {
        if ( ! $this->conn) {
            $this->initConnection();
        }
        return $this->conn;
    }

    /**
     * begin a new transaction
     *
     * @return bool true on success
     */
    public function beginTransaction()
    {
        $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        return $this->conn->beginTransaction();
    }

    /**
     * commit the current transaction
     *
     * @return bool true on success
     */
    public function commit()
    {
        $rc = $this->conn->commit();
        $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        return $rc;
    }

    /**
     * cancel and roll back any changes made under the current transaction
     *
     * @return bool true on success
     */
    public function rollBack()
    {
        $rc = $this->conn->rollBack();
        $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        return $rc;
    }

    /**
     * log this query to the output (if $this->debug is true) and/or to the
     * sql log file (if LOG_FILE has been defined).
     *
     * @param string $sql sql string
     * @param array $bind_data optional array of variables being bound into sql
     * @param PDOException $e
     */
    public function debugQuery($sql, $bind_data = array(), PDOException $e = null)
    {
        if ($this->debug || defined('LOG_SQL')) {
            //replace bind vars
            if (count($bind_data)) {
                foreach ($bind_data as $k => $v) {
                    $sql = preg_replace("/:$k/", "'$v'", $sql);
                }
            }
            $errInfo = '';
            if ( ! is_null($e)) {
                $errInfo = "\n\n" . $e->getMessage();
            }
            if ($this->debug) {
                echo "<div class=\"debug\"><pre>$sql$errInfo</pre></div>";
            }
            if (defined('LOG_SQL')) {
                fwrite($this->log_file, "$sql;$errInfo\n");
            }
        }
    }

    /**
     * executes a sql query against the current database. the result of this query
     * is retained for further processing (as an open cursor).
     *
     * @param string $sql the sql statement
     * @param mixed $bind_data optional array of parameters to be bound into the statement
     * @return PDOStatement
     */
    public function query($sql, $bind_data = array())
    {
        if ($this->statement !== false) {
            $this->statement->closeCursor();
        }
        $this->statement = $this->blindQuery($sql, $bind_data);
        return $this->statement;
    }

    /**
     * using the cursor previously created by query(), return a row from the database
     *
     * @param integer $mode PDO::FETCH_* constants. will default to PDO::FETCH_ASSOC
     * @return mixed
     */
    public function fetch($mode = PDO::FETCH_ASSOC)
    {
        return $this->statement->fetch($mode);
    }

    /**
     * executes a sql query against the current database. the result of this query is NOT retained.
     *
     * @param string $sql the sql statement
     * @param mixed $bind_data optional array of parameters to be bound into the statement
     * @return PDOStatement
     */
    public function blindQuery($sql, $bind_data = array())
    {
        $this->debugQuery($sql, $bind_data);
        if ( ! $this->conn) {
            $this->initConnection(); //we init this here, so we don't create a conn if no query takes place
        }
        try {
            $this->exception = null;
            if (count($bind_data)) {
                //use prepared statement syntax
                $statement = $this->conn->prepare($sql);
                $statement->execute($bind_data);
            } else {
                //regular old query
                $statement = $this->conn->query($sql);
            }
        } catch (PDOException $e) {
            if (defined('DEV_SERVER')) {
                $this->debug = true;
                $this->debugQuery($sql, $bind_data, $e);
                die('Unable to execute query ' . $this->conn->errorCode());
            }
            $this->exception = $e;
            error_log($e->getMessage());
        }
        return $statement;
    }

    /**
     * returns all rows selected by a sql statement
     *
     * @param string $sql
     * @param array $bind_data optional array of parameters to be bound into the statement
     * @return array
     */
    public function fetchAll($sql, $bind_data = array())
    {
        $_SESSION['sql'] = $sql;
        $stmt = $this->blindQuery($sql, $bind_data);
        if ($stmt !== false) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return array();
    }

    /**
     * when you just need one row
     *
     * @param string $sql
     * @param array $bind_data optional array of parameters to be bound into the statement
     * @return array
     */
    public function fetchRow($sql, $bind_data = array())
    {
        $stmt = $this->blindQuery($sql, $bind_data);
        if ($stmt !== false) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return array();
    }

    /**
     * when you just need one field
     *
     * @param string $sql
     * @param array $bind_data optional array of parameters to be bound into the statement
     * @param integer $index optional numeric index of the column to return
     * @return string
     */
    public function fetchColumn($sql, $bind_data = array(), $index = 0)
    {
        $stmt = $this->blindQuery($sql, $bind_data);
        if ($stmt !== false) {
            return $stmt->fetchColumn($index);
        }
        return null;
    }

    /**
     * get the last insert id--the id of the row created by the most recent insert statement
     *
     */
    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * safely quote a string (escape any characters necessary) so that it may be
     * used in a sql statement
     *
     * @param string $string
     * @param integer $style optional PDO data type hint
     * @return string
     */
    public function quote($string, $style = PDO::PARAM_STR)
    {
        // calls to this routine usually happen before the calls to query()
        // so we probably need to create the connection now
        if ( ! $this->conn) {
            $this->initConnection();
        }
        return $this->conn->quote($string, $style);
    }

    public function setDebug($dbg)
    {
        $this->debug = $dbg == true;
    }

    /**
     * returns a copy of the PDOException encountered on the last query or null
     *
     * @return PDOException
     */
    public function getException()
    {
        return $this->exception;
    }
}
