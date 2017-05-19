<?php
/** Universal stackable classloader.
*
* @version SVN: $Id: classloader.php 632 2017-05-19 13:40:51Z anrdaemon $
*/

namespace AnrDaemon;

use SplFileInfo;

spl_autoload_register(function($className)
{
  if(strstr($className, '\\', true) !== __NAMESPACE__)
    return;

  $file = new SplFileInfo(__DIR__ . strtr(strstr("$className.php", '\\'), '\\', '/'));
  $path = $file->getRealPath();
  if(!empty($path))
  {
    include_once $path;
  }
});
