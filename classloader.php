<?php
/** Universal stackable classloader.
*
* @version SVN: $Id: classloader.php 619 2016-12-02 19:15:47Z anrdaemon $
*/

namespace AnrDaemon;

use SplFileInfo;

$JeQa5VZ1eB13zrb1 = strlen(__NAMESPACE__);

spl_autoload_register(function($className) use($JeQa5VZ1eB13zrb1)
{
  if(strncasecmp($className, __NAMESPACE__ . '\\', $JeQa5VZ1eB13zrb1 + 1) !== 0)
    return;

  $file = new SplFileInfo(__DIR__ . strtr(substr("$className.php", $JeQa5VZ1eB13zrb1), '\\', '/'));
  $path = $file->getRealPath();
  if(!empty($path))
  {
    include_once $path;
  }
});

unset($JeQa5VZ1eB13zrb1);
