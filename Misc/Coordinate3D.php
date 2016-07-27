<?php
/** Base GPS coordinates class
*
* @version SVN: $Id: Coordinate3D.php 609 2016-07-27 01:33:09Z anrdaemon $
*/

namespace AnrDaemon\Misc;

use
  ArrayAccess, Countable, Iterator,
  InvalidArgumentException, LogicException;

class Coordinate3D
  implements
    ArrayAccess,
    Countable,
    Iterator
{
  protected $gps = array(
    'x' => 0,
    'y' => 0,
    'z' => 0,
  );

  protected $precision = 2;

  public function __construct($x, $y, $z, $precision = 2)
  {
    if(!is_numeric($x) || !is_numeric($y) || !is_numeric($z) || !is_numeric($precision))
      throw new InvalidArgumentException('All arguments must be numeric.');

    $this->gps['x'] = (float)$x;
    $this->gps['y'] = (float)$y;
    $this->gps['z'] = (float)$z;
    $this->precision = (int)$precision;
  }

  /** Construct coordinate by the vector length and direction.
  *
  * @param $length the vector length
  * @param $ap angle in XY plane (in radians)
  * @param $av angle off XY plane (in radians)
  */
  public static function fromPolar($length, $ap, $av)
  {
    return new static($length * cos($ap) * cos($av), $length * sin($ap) * cos($av), $length * sin($av));
  }

  /** Distance between two coordinates.
  *
  * @param Coordinate3D|int $target|$x the point to calculate distance to
  * @param ?int $y the point to calculate distance to
  * @param ?int $z the point to calculate distance to
  * @return float distance
  */
  public function distance($target, $y = null, $z = null)
  {
    if($target instanceof self)
    {
      $_x = $this->gps['x'] - $target->gps['x'];
      $_y = $this->gps['y'] - $target->gps['y'];
      $_z = $this->gps['z'] - $target->gps['z'];
    }
    else
    {
      $_x = $this->gps['x'] - $target;
      $_y = $this->gps['y'] - $y;
      $_z = $this->gps['z'] - $z;
    }
    return sqrt($_x*$_x + $_y*$_y + $_z*$_z);
  }

  /** Translate coordinate in space
  *
  * @param Coordinate3D|int $shift|$x the distances by which a coordinate must be translated.
  * @param ?int $y the distances by which a coordinate must be translated.
  * @param ?int $z the distances by which a coordinate must be translated.
  * @return Coordinate3D the translated coordinate
  */
  public function translate($shift, $y = null, $z = null)
  {
    if($shift instanceof self)
      return new static($this->gps['x'] + $shift->gps['x'], $this->gps['y'] + $shift->gps['y'], $this->gps['z'] + $shift->gps['z']);
    else
      return new static($this->gps['x'] + $shift, $this->gps['y'] + $y, $this->gps['z'] + $z);
  }

  public function format($format = null, $decimals = null, $thousands = null)
  {
    if(isset($format))
    {
      if(isset($decimals) || isset($thousands))
      {
        if(isset($decimals) && isset($thousands))
          return sprintf($format,
            number_format($this->gps['x'], $this->precision, $decimals, $thousands),
            number_format($this->gps['y'], $this->precision, $decimals, $thousands),
            number_format($this->gps['z'], $this->precision, $decimals, $thousands)
            );
        else
          throw new InvalidArgumentException('Decimals and thousands separators must be both set or both unset.');
      }
      else
        return sprintf($format, $this->gps['x'], $this->gps['y'], $this->gps['z']);
    }
    else
      return sprintf('%s, %s, %s',
        number_format($this->gps['x'], $this->precision, '.', "'"),
        number_format($this->gps['y'], $this->precision, '.', "'"),
        number_format($this->gps['z'], $this->precision, '.', "'")
        );
  }

  final public function __get($name)
  {
    if(isset($this->gps[$name]))
      return $this->gps[$name];
    else if($name == "precision")
      return $this->precision;

    throw new LogicException("No such property \`{$name}\' or property is not readable.");
  }

  final public function __set($name, $value)
  {
    if($name == "precision")
    {
      $this->precision = (int)$value;
      return;
    }

    throw new LogicException("No such property \`{$name}\', invalid value for property or property is not writable.");
  }

  final public function __toString()
  {
    return $this->format();
  }

  final public function __debugInfo()
  {
    return array('gps' => $this->gps, 'precision' => $this->precision);
  }

// \ArrayAccess implementation for coordinates.

  public function offsetExists($offset)
  {
    return isset($this->gps[$offset]);
  }

  public function offsetGet($offset)
  {
    return $this->$offset;
  }

  public function offsetSet($offset, $value)
  {
    if(!isset($this->gps[$offset]))
      throw new LogicException('Forbidden.');

    $this->gps[$offset] = (float)$value;
  }

  public function offsetUnset($offset)
  {
    throw new LogicException('Forbidden.');
  }

// \Countable

  public function count()
  {
    return count($this->gps);
  }

// \Iterator

  protected $position = 0;

  public function current()
  {
    return $this->valid() ? current($this->gps) : null;
  }

  public function key()
  {
    return $this->valid() ? key($this->gps) : null;
  }

  public function next()
  {
    if($this->valid())
    {
      $this->position++;
      next($this->gps);
    }
    return;
  }

  public function rewind()
  {
    reset($this->gps);
    $this->position = 0;
    return;
  }

  public function valid()
  {
    return $this->position < count($this->gps);
  }
}
