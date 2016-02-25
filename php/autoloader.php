<?php
/** \AnrDaemon autoloader.
*
* @version SVN: $Id: autoloader.php 455 2016-02-16 00:51:31Z anrdaemon $
*/

namespace AnrDaemon;

function spl_autoload($className)
{
  // Absolutely minimal "AnrDaemon\?" class name
  if(strlen($className) < 11)
    return false;

  $file = new \SplFileInfo(__DIR__ . substr(strtr("$className.php", '\\', '/'), 9));
  $path = $file->getRealPath();
  if(empty($path))
  {
    return false;
  }
  else
  {
    return include_once $path;
  }
}

\spl_autoload_register('\AnrDaemon\spl_autoload');
