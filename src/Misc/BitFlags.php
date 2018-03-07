<?php
/** Bitmask to string flags and back conversion tools.
*
* @version SVN: $Id: BitFlags.php 742 2018-03-07 00:34:40Z anrdaemon $
*/

namespace AnrDaemon\Misc;

class BitFlags
{
  protected $dictionary;
  protected $limit;

  public function toFlags($mask)
  {
    $len = $mask > 0 ? ceil(log($mask, 2)) : PHP_INT_SIZE * 8;
    $flags = '';
    for($i = 0, $bit = 1; $i < $this->limit, $i < $len, $bit != 0; $i++, $bit <<= 1)
    {
      if($mask & $bit)
      {
        $flags .= $this->dictionary[$i];
      }
    }

    return $flags;
  }

  public function toMask($flags)
  {
    $len = strlen($flags);
    $mask = 0;
    for($i = 0, $bit = 1; $i < $len; $i++, $bit <<= 1)
    {
      $pos = strpos($this->dictionary, $flags[$i]);
      if($pos !== false)
      {
        $mask |= 1 << $pos;
      }
    }

    return $mask;
  }

// Magic!

  public function __construct($dictionary)
  {
    if($dictionary === '')
      throw new \LengthException("Dictionary can't be empty.");

    $this->dictionary = substr($dictionary, 0, PHP_INT_SIZE * 8);
    $this->limit = strlen($this->dictionary);

    if($dictionary !== $this->dictionary)
    {
      trigger_error('Dictionary truncated to fit native integer length.');
    }
  }
}
