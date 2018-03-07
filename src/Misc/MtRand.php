<?php
/** mt_rand based RNG wrappers.
*
* @version SVN: $Id: MtRand.php 738 2018-03-03 19:03:36Z anrdaemon $
*/

namespace AnrDaemon\Misc;

const MT_RANDMAX = 0x7fffffff;
if(MT_RANDMAX != mt_getrandmax())
  throw new \RangeException('mt_getrandmax() != 0x7fffffff, call ambulance.');

final class MtRand
{
  private function __construct() {}
  private function MtRand() {}

/** mt_rand based 0..+1 random number.
*/
  public static function p()
  {
    return mt_rand() / MT_RANDMAX;
  }

/** mt_rand based -1..+1 random number.
*/
  public static function f()
  {
    return 2 * (mt_rand() / MT_RANDMAX) - 1;
  }
}
