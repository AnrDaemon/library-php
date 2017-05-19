<?php
/** PDO SQLite enhancement wrapper
*
* @package Wrappers
* @version SVN: $Id: PDOSqlite.php 620 2016-12-12 22:23:54Z anrdaemon $
*/

namespace AnrDaemon\Wrappers;

use Collator, Locale, PDO;

class PDOSqlite extends PDOWrapper
{
  /** PDO::__construct() wrapper enables SQLite specific functionality
  *
  * @param string $dsn
  * @param string $username
  * @param string $password
  * @param array $options
  * @return PDOSqlite
  *
  * @see PDO::__construct()
  * @see AnrDaemon\Wrappers\PDO::__construct()
  */
  public function __construct($dsn, $username = null, $password = null, $options = array())
  {
    parent::__construct($dsn, $username, $password, $options);

    if($this->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite')
    {
      $this->sqliteCreateFunction('regexp',
        function($y, $x) { return preg_match("{{$y}}", $x); }, 2);

      $c = $this->createCollator('C_x_CI_AI');

      $this->sqliteCreateFunction('strcmp', [$c, 'compare'], 2);
      $this->registerCollation('utf8_general_ci', [$c, 'compare']);
    }
  }

  private function createCollator($ident)
  {
    // Temp. collator instance
    $c = new Collator('POSIX');
    $c->setStrength(1);
    $c->setAttribute(Collator::CASE_FIRST, Collator::UPPER_FIRST);
    $c->setAttribute(Collator::CASE_LEVEL, Collator::OFF);

    return $c;
  }

  public function registerCollation($name, $ident)
  {
    // Assign collator as closure.
    $this->sqliteCreateCollation($name, [$this->createCollator($ident), 'compare']);
  }
}
__halt_compiler();
// Temp. collator instance
$c = new \Collator('POSIX');
$c->setStrength(1);
$c->setAttribute(\Collator::CASE_FIRST, \Collator::UPPER_FIRST);
$c->setAttribute(\Collator::CASE_LEVEL, \Collator::OFF);

printf("Collation locale: %s %s\n", $c->getLocale(\Locale::VALID_LOCALE), $c->getLocale(\Locale::ACTUAL_LOCALE));

// Assign collator as closure.
$db->sqliteCreateFunction('strcmp', [$c, 'compare'], 2);
$db->sqliteCreateCollation('utf8_general_ci', [$c, 'compare']);

// Do not forget to destroy temp. collator to not get caught by surprize.
unset($c);

vprintf("Expect: %2d got: %2d\n", $db->query('SELECT "0" `expect`, strcmp("Мама", "мама") `result`')->fetch());
vprintf("Expect: %2d got: %2d\n", $db->query('SELECT "-1" `expect`, strcmp("е", "Ё") `result`')->fetch());
vprintf("Expect: %2d got: %2d\n", $db->query('SELECT "-1" `expect`, strcmp("ё", "ж") `result`')->fetch());

$db->query('
  CREATE TABLE IF NOT EXISTS `routes` (
    `method` TEXT COLLATE binary NOT NULL DEFAULT (\'*\'),
    `location` TEXT COLLATE utf8_general_ci,
    `type` TEXT COLLATE nocase NOT NULL DEFAULT (\'default\')
  )');

$db->query('
  INSERT INTO `routes`
    (`method`, `location`, `type`)
  VALUES
    ("*", "е", "default"),
    ("GET", "ё", "regexp"),
    ("POST", "Ё", "exact"),
    ("PUT", "ж", "prefix")
');

$rc = $db->query("SELECT `location` FROM `routes` ORDER BY `location` ASC");
$ta = [];
foreach($rc as $row)
{
  $ta[] = $row['location'];
}
print 'ASC:  ' . implode('/', $ta) . "\n";

$rc = $db->query("SELECT `location` FROM `routes` ORDER BY `location` DESC");
$ta = [];
foreach($rc as $row)
{
  $ta[] = $row['location'];
}
print 'DESC: ' . implode('/', $ta) . "\n";

$q = $db->prepare('
  SELECT ? `expect`, COUNT(*) `result`, `method`
  FROM `routes`
  WHERE `method` = ?
  GROUP BY `method`');

foreach(["get" => 0, "Get" => 0, "GET" => 1] as $method => $expect)
{
  $q->execute([$expect, $method]);
  $rc = $q->fetch();
  if($rc)
  {
    vprintf("Expect: %2d got: %2d value: %s\n", $rc);
  }
  else
  {
    printf("Expect: %2d got: %2d value: %s\n", $expect, 0, $method);
  }
}

vprintf("Expect: %2d got: %2d value: %s\n", $db->query('
  SELECT 3 `expect`, COUNT(*) `result`, MAX(`t1`)
  FROM (
    SELECT r1.`type` t1, NULL t2, NULL t3 FROM `routes` r1
    WHERE r1.`type` = "default"
    UNION
    SELECT NULL t1, r2.`type` t2, NULL t3 FROM `routes` r2
    WHERE r2.`type` = "DeFaUlT"
    UNION
    SELECT NULL t1, NULL t2, r3.`type` t3 FROM `routes` r3
    WHERE r3.`type` = "DEFAULT"
  )')->fetch());
