<?php
/** PDO chaining wrapper and syntactic sugar.
*
* @package Wrappers
* @version SVN: $Id: PDOWrapper.php 653 2017-06-05 23:02:17Z anrdaemon $
*/

namespace AnrDaemon\Wrappers;

use PDO;

class PDOWrapper extends PDO
{
  /** PDO::__construct() wrapper with some necessary stuff.
  *
  * Forces PDO::ERRMODE_EXCEPTION.
  * Defaults to PDO::FETCH_ASSOC.
  * Defaults to disable emulated prepares.
  *
  * @param string $dsn
  * @param string $username
  * @param string $password
  * @param array $options
  * @return PDOWrapper
  *
  * @see PDO::__construct()
  */
  public function __construct($dsn, $username = null, $password = null, $options = array())
  {
    // Force exceptions over return codes.
    $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

    // Disables emulated prepares if not told otherwise
    if(!isset($options[PDO::ATTR_EMULATE_PREPARES]))
      $options[PDO::ATTR_EMULATE_PREPARES] = false;

    // Set default fetch mode to PDO::FETCH_ASSOC if nothing else is specified.
    if(!isset($options[PDO::ATTR_DEFAULT_FETCH_MODE]))
      $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

    parent::__construct($dsn, $username, $password, $options);
  }

  /** PDO::setAttribute() chaining wrapper.
  *
  * @param int attribute identifier from PDO::ATTR_* list.
  * @param mixed attribute value.
  * @return PDOWrapper
  *
  * @see PDO::setAttribute()
  */
  public function setAttribute($attribute, $value)
  {
    parent::setAttribute($attribute, $value);
    return $this;
  }

  /** PDO::prepare()::execute() chaining wrapper.
  *
  * @param string SQL query with placeholders.
  * @param array substitution variables.
  * @return PDOStatement
  *
  * @see PDO::prepare()
  * @see PDOStatement::execute()
  */
  public function run($query, $arguments = array())
  {
    $stmt = $this->prepare($query);
    $stmt->execute($arguments);
    return $stmt;
  }

  /** PDO::prepare()::execute()::fetch() chaining wrapper.
  *
  * @param string SQL query with placeholders.
  * @param array substitution variables.
  * @return mixed[] first row of the resultset.
  *
  * @see PDO::prepare()
  * @see PDOStatement::execute()
  * @see PDOStatement::fetch()
  */
  public function get($query, $arguments = array())
  {
    return $this->run($query, $arguments)->fetch();
  }

  /** PDO::prepare()::execute()::fetchColumn() chaining wrapper.
  *
  * @param string SQL query with placeholders.
  * @param array substitution variables.
  * @param int column number to retrieve value from.
  * @return mixed value of the $column_number's column from the first row of the resultset.
  *
  * @see PDO::prepare()
  * @see PDOStatement::execute()
  * @see PDOStatement::fetchColumn()
  */
  public function getColumn($query, $arguments = array(), $column_number = 0)
  {
    return $this->run($query, $arguments)->fetchColumn($column_number);
  }

  /** PDO::prepare()::execute()::fetchAll() chaining wrapper.
  *
  * @param string SQL query with placeholders.
  * @param array substitution variables.
  * @return array the entire resultset as an array.
  *
  * @see PDO::prepare()
  * @see PDOStatement::execute()
  * @see PDOStatement::fetchAll()
  */
  public function getAll($query, $arguments = array())
  {
    return $this->run($query, $arguments)->fetchAll();
  }
}
