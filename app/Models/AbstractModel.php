<?php
namespace App\Models;

/**
 * Provides minimal object/relational mapping.  Supports a single table.
 * Does not understand foreign keys or relationships.
 *
 * Configure as follows:
 *
 *  class bob extends AbstractModel {
 *    function __construct($id = null) {
 *       parent::__construct();
 *       $this->setTable('bob_table');
 *       $this->setIdentifierField('id');
 *       if( ! is_null($id)) {
 *         $this->setId($id);
 *       }
 *    }
 *  }
 *
 * Then you can use the object as follows:
 *
 *  $bob = new bob(1);
 *  $bob->email = 'bob@dobbs.net';
 *  $bob->save();
 *
 *
 * @author Michal Carson <michal.carson@carsonsoftwareengineering.com>
 * @copyright (c) 2014, Carson Software Engineering
 *
 */

abstract class AbstractModel
{
    /**
     * name of the database table
     * @var string
     */
    protected $_table;
    /**
     * name of the primary key field on the table
     * @var string
     */
    protected $_id_field;
    /**
     * value of the primary key for this record (if it already exists)
     * @var mixed
     */
    protected $_id;
    /**
     * name/value pairs for the field to be updated on the table
     * @var array
     */
    protected $_data = array();
    /**
     * name/value pairs from the database
     * @var array
     */
    protected $_done = array();
    /**
     * @var DatabaseEncryptor
     */
    protected $_encryptor;
    /**
     * list of fields that must be encrypted when committed to storage
     * @var array of field names
     */
    protected $_encrypted = array();

    public function __construct()
    {
        $this->_encryptor = new DatabaseEncryptor();
    }

    /**
     * magic method allows field values to be set with $obj->fieldname syntax
     * @param string $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (is_scalar($value)) {
            $this->_data[$name] = $value;
        }
    }

    /**
     * magic method allows field values to be retrieved with $obj->fieldname syntax
     * @param string $name
     * @return
     */
    public function __get($name)
    {
        if (isset($this->_data[$name])) {
            // return a value that is waiting to be saved
            return $this->_data[$name];
        }
        if ( ! count($this->_done)) {
            // we need to retrieve the record
            $this->_done = $this->read();
        }
        if (array_key_exists($name, $this->_done)) {
            // return the value as it looks in the db
            return $this->_done[$name];
        }
        // last chance. look for a getter method.
        $method = 'get' . str_replace('_', '', $name);
        if (method_exists($this, $method)) {
            // you can use this approach to return relationships (e.g. getLineItems())
            return $this->$method();
        }
        return $this->getDefault($name);
    }

    public function setTable($name)
    {
        $this->_table = $name;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function setIdentifierField($name)
    {
        $this->_id_field = $name;
    }

    public function getIdentifierField()
    {
        return $this->_id_field;
    }

    public function setId($value)
    {
        $this->_id = $value;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function save()
    {
        // have we been given anything to save?
        if (count($this->_data)) {
            $this->preSave();
            // do we have the key for an existing record?
            if (isset($this->_id)) {
                $this->update();
            } else {
                $this->insert();
            }
            $this->postSave();
        }
    }

    public function read()
    {
        $this->preRead();
        $sql = "select * from `$this->_table`
                where `$this->_id_field` = :$this->_id_field";
        $bind = array($this->_id_field => $this->_id);
        $db = Database::getInstance();
        if ($row = $db->fetchRow($sql, $bind)) {
            foreach ($row as $field => $value) {
                if (is_numeric($field)) continue;
                $this->_done[$field] = $value;
            }
        }
        $this->decrypt();
        $this->postRead();
        return $this->_done;
    }

    public function update()
    {
        $this->preUpdate();
        $this->encrypt();
        $fields = array();
        foreach ($this->_data as $field => $value) {
            $fields[] = "`$field` = :$field";
        }
        $sql = "update `$this->_table` set "
            . implode(', ', $fields)
            . " where `$this->_id_field` = :$this->_id_field";
        $bind = $this->_data;
        $bind[$this->_id_field] = $this->_id;
        $db = Database::getInstance();
        $db->blindQuery($sql, $bind);
        $this->_data = $this->_done = array();
        $this->postUpdate();
    }

    public function insert()
    {
        $this->preInsert();
        $this->encrypt();
        $bind = $this->_data;
        unset($bind[$this->_id_field]);
        $fields = array_keys($bind);
        $sql = "insert into `$this->_table` (`"
            . implode('`, `', $fields)
            . "`) values (:"
            . implode(", :", $fields)
            . ")";
        $db = Database::getInstance();
        $db->blindQuery($sql, $bind);
        $this->_data = $this->_done = array();
        $this->_id = $id = $db->lastInsertId();
        $this->postInsert();
        return $id;
    }

    public function delete()
    {
        $this->preDelete();
        $sql = "delete from `$this->_table`
            where `$this->_id_field` = :$this->_id_field";
        $bind = array($this->_id_field => $this->_id);
        $db = Database::getInstance();
        $db->blindQuery($sql, $bind);
        $this->_data = $this->_done = array();
        $this->postDelete();
    }

    /**
     * returns all data elements from the base table as an array
     * @return array
     * @throws \Exception    if un-saved changes are detected
     */
    public function toArray()
    {
        if (count($this->_data) == 1 and array_key_exists($this->_id_field, $this->_data)) {
            // the key has been set but we can ignore this one field
        } else if (count($this->_data)) {
            // we have un-saved data
            throw new \Exception('Inconsistent state. Unsaved changes.');
        }
        if (count($this->_done)) {
            // we have already read the database so use that
            return $this->_done;
        }
        // get the data from the database
        return $this->read();
    }

    /**
     * find the default defined by the table definition in the database
     *
     * @param string $name column name
     * @return mixed
     */
    protected function getDefault($name)
    {
        $sql = "show columns from `$this->_table`
                like '$name'";
        $db = Database::getInstance();
        if ($row = $db->fetchRow($sql)) {
            return $row['Default'];
        }
        if (defined('DEV_SERVER')) {
            echo "$name was not found for table $this->_table.<pre>";
            debug_print_backtrace();
            echo '</pre>';
        }
        die("$name was not found for table $this->_table.");
    }

    /**
     * designate which fields require encryption when committed to storage
     * (and decryption when read back). be sure your field size is large enough
     * to hold the full encrypted data.
     *
     * @param array $fields
     */
    public function setEncryptedFields(array $fields)
    {
        $this->_encrypted = $fields;
    }

    /**
     * encrypt designated fields on the data that is about to be written to the database
     */
    protected function encrypt()
    {
        foreach ($this->_encrypted as $field) {
            if (array_key_exists($field, $this->_data) and strlen($this->_data[$field])) {
                $this->_data[$field] = $this->_encryptor->encrypt($this->_data[$field]);
            }
        }
    }

    /**
     * decrypt designated fields that have just been read from the database
     */
    protected function decrypt()
    {
        foreach ($this->_encrypted as $field) {
            if (array_key_exists($field, $this->_done) and strlen($this->_done[$field])) {
                $this->_done[$field] = $this->_encryptor->decrypt($this->_done[$field]);
            }
        }
    }

    protected function preSave()
    {
    }

    protected function postSave()
    {
    }

    protected function preRead()
    {
    }

    protected function postRead()
    {
    }

    protected function preInsert()
    {
    }

    protected function postInsert()
    {
    }

    protected function preUpdate()
    {
    }

    protected function postUpdate()
    {
    }

    protected function preDelete()
    {
    }

    protected function postDelete()
    {
    }
}
