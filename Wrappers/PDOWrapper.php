<?php
/** PDO chaining wrapper and syntactic sugar.
*
* @package Wrappers
* @version SVN: $Id: PDOWrapper.php 634 2017-05-22 13:27:59Z anrdaemon $
*/

namespace AnrDaemon\Wrappers;

use PDO;

class PDOWrapper extends PDO
{
  /** PDO::__construct() wrapper with some necessary stuff.
  *
  * Forces PDO::ERRMODE_EXCEPTION.
  * Defaults to PDO::FETCH_ASSOC.
  * Fixes pdo_mysql not honouring charset=... in DSN  in PHP < 5.3.6.
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

    // Hack for PHP < 5.3.6 not honoring charset= in DSN.
    // Keep in mind this is NOT entirely safe for ethereal character sets.
    // But it is fairly fine for UTF-8 and compatible single-byte encodings.
    if(preg_match('/^mysql:(?P<params>.*)$/i', trim($dsn), $tdsn))
    {
      if(!empty($options[PDO::MYSQL_ATTR_INIT_COMMAND]))
        throw new PDOException('Please don\'t use init command manually.');

      $list = array();
      foreach(explode(';', $tdsn['params']) as $part)
      {
        if(preg_match('/\b(?P<name>\w+)=(?P<value>\S+)/i', trim($part), $ta))
        {
          switch($ta['name'])
          {
            case 'charset':
              if(version_compare(PHP_VERSION, '5.3.6', '<'))
              {
                if(preg_match('/^(?P<charset>\w+)/', $ta['value'], $tb))
                  $list[] = "NAMES {$tb['charset']}";
                else
                  throw new PDOException('Old PHP: Unable to determine charset: ' . $ta['value']);
              }
              break;
            case 'timezone':
              /*
                SELECT * FROM mysql.time_zone_name t
                WHERE t.`Name` NOT REGEXP '^[[:alpha:]][[:alnum:]\_]*([/+-][[:alnum:]\_]+)*$'
              */
              if(preg_match('/^(?P<tz>[a-z]\w*(?:[\/\+\-]\w+)*|[\+\-]\d+(?:\:\d+)+)/i', $ta['value'], $tb))
                $list[] = sprintf("`time_zone` = '%s'", addcslashes($tb['tz'], "'\\"));
              else
                throw new \PDOException('Unable to determine timezone: ' . $ta['value']);
              break;
          }
        }
      }

      if(!empty($list))
      {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET " . implode(", ", $list);
      }
    }

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
