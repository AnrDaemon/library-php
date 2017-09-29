<?php
/** PDOWrapper for MySQL with extensions.
*
* @package Wrappers
* @version SVN: $Id: PDOMysql.php 683 2017-09-29 01:06:21Z anrdaemon $
*/

namespace AnrDaemon\Wrappers;

use PDO, PDOException;

class PDOMysql extends PDOWrapper
{
  /** PDO::__construct() wrapper with some necessary stuff.
  *
  * Fixes pdo_mysql not honouring charset=... in DSN  in PHP < 5.3.6.
  * Adds user timezone in DSN.
  * Adds user lc_time in DSN.
  *
  * @param string $dsn
  * @param string $username
  * @param string $password
  * @param array $options
  * @return PDOMysql
  *
  * @see PDO::__construct()
  */
  public function __construct($dsn, $username = null, $password = null, array $options = array())
  {
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
        if(preg_match('/^\b(?P<name>\w+)=(?P<value>\S+)/i', trim($part), $ta))
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
            case 'lc_time':
              if(preg_match('/^(?P<lang>[a-z][a-z](?:[-_]\w+)*)/', $ta['value'], $tb))
                $list[] = sprintf("`lc_time_names` = '%s'", $tb['lang']);
              else
                throw new PDOException('Unable to determine locale: ' . $ta['value']);
              break;
            case 'timezone':
              /*
                SELECT * FROM mysql.time_zone_name t
                WHERE t.`Name` NOT REGEXP '^[[:alpha:]][[:alnum:]\_]*([/+-][[:alnum:]\_]+)*$'
              */
              if(preg_match('/^(?P<tz>[a-z]\w*(?:[\/\+\-]\w+)*|[\+\-]\d+(?:\:\d+)+)/i', $ta['value'], $tb))
                $list[] = sprintf("`time_zone` = '%s'", addcslashes($tb['tz'], "'\\"));
              else
                throw new PDOException('Unable to determine timezone: ' . $ta['value']);
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
}
