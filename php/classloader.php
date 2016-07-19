<?php
/** Universal stackable classloader.
*
* @version SVN: $Id: classloader.php 530 2016-07-18 18:42:58Z anrdaemon $
*/

spl_autoload_register(function($className){
  $file = new SplFileInfo(__DIR__ . strtr(substr("$className.php", 9), '\\', '/'));
  $path = $file->getRealPath();
  if(!empty($path))
  {
    include_once $path;
  }
});
