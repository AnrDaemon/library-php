<?php
/** Universal stackable classloader.
*
* @version SVN: $Id: classloader.php 533 2016-07-19 20:05:21Z anrdaemon $
*/

spl_autoload_register(function($className){
  if(strncasecmp($className, 'AnrDaemon\\', 10) !== 0)
    return;
  $file = new SplFileInfo(__DIR__ . strtr(substr("$className.php", 9), '\\', '/'));
  $path = $file->getRealPath();
  if(!empty($path))
  {
    include_once $path;
  }
});
