<?php
/** PDO chaining wrapper and syntactic sugar.
*
* @package Wrappers
* @version SVN: $Id: PDOWrapper.php 491 2016-02-24 12:58:51Z anrdaemon $
*/

namespace AnrDaemon\Wrappers;

class PDOWrapper extends \PDO
{
  /** PDO::__construct() wrapper with some necessary stuff.
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
    $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

    // Set default fetch mode to PDO::FETCH_ASSOC if nothing else is specified.
    if(!isset($options[\PDO::ATTR_DEFAULT_FETCH_MODE]))
      $options[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;

    // Hack for PHP < 5.3.6 not honoring charset= in DSN.
    // Keep in mind this is NOT entirely safe for ethereal character sets.
    // But it is fairly fine for UTF-8 and most of western single-byte encodings.
    if(version_compare(PHP_VERSION, '5.3.6', '<') && preg_match('/^mysql:.*\bcharset=(?P<charset>\w+)/i', trim($dsn), $ta))
      $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$ta['charset']}";

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
  * @return mixed first column of the first row of the resultset.
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
  * @return mixed first column of the first row of the resultset.
  *
  * @see PDO::prepare()
  * @see PDOStatement::execute()
  * @see PDOStatement::fetchColumn()
  */
  public function getColumn($query, $arguments = array())
  {
    return $this->run($query, $arguments)->fetchColumn();
  }

  /** PDO::prepare()::execute()::fetchAll() chaining wrapper.
  *
  * @param string SQL query with placeholders.
  * @param array substitution variables.
  * @return mixed first row of the resultset.
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
