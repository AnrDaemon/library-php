<?php
/** Base GPS coordinates class
*
* @version SVN: $Id: Point.php 800 2018-04-15 17:21:44Z anrdaemon $
*/

namespace AnrDaemon\Math;

class Point
implements \ArrayAccess
{
  protected $format = '%8.4g, %8.4g, %8.4g';

  protected $gps = array(
    'x' => null,
    'y' => null,
    'z' => null,
    'vl' => null,
    'ap' => null,
    'av' => null,
  );

  /** Construct point from cartesian coordinates provided.
  *
  * @param numeric $x
  * @param numeric $y
  * @param numeric $z
  */
  public static function fromCartesian($x, $y, $z)
  {
    return new static($x, $y, $z);
  }

  /** Helper function to fill in missing properties, if needed.
  *
  * @param $name the name of the requested property.
  * @return float the property value.
  */
  protected function toCartesian($name)
  {
    if(!isset($this->gps['x'], $this->gps['y'], $this->gps['z']))
    {
      if(!isset($this->gps['vl'], $this->gps['ap'], $this->gps['av']))
        throw new \LogicException('Somehow, you managed to initialize instance with partial data.');

      $this->gps['x'] = $this->gps['vl'] * cos($this->gps['ap']) * cos($this->gps['av']);
      $this->gps['y'] = $this->gps['vl'] * sin($this->gps['ap']) * cos($this->gps['av']);
      $this->gps['z'] = $this->gps['vl'] * sin($this->gps['av']);
    }

    return $this->gps[$name];
  }

  /** Construct point by the vector length and direction (in radians).
  *
  * @param $length the vector length
  * @param $ap angle in XY plane (in radians)
  * @param $av angle off XY plane (in radians)
  */
  public static function fromPolar($length, $ap, $av)
  {
    return new static($length, $ap, $av, true);
  }

  /** Construct coordinate by the vector length and direction (in degrees).
  *
  * @param $length the vector length
  * @param $ap angle in XY plane (in degrees)
  * @param $av angle off XY plane (in degrees)
  */
  public static function fromPolarDeg($length, $ap, $av)
  {
    return static::fromPolar($length, deg2rad($ap), deg2rad($av));
  }

  /** Helper function to fill in missing properties, if needed.
  *
  * @param $name the name of the requested property.
  * @return float the property value.
  */
  protected function toPolar($name)
  {
    if(!isset($this->gps['vl'], $this->gps['ap'], $this->gps['av']))
    {
      if(!isset($this->gps['x'], $this->gps['y'], $this->gps['z']))
        throw new \LogicException('Somehow, you managed to initialize instance with partial data.');

      $this->gps['vl'] = $this->distance();
      $this->gps['ap'] = atan2($this->gps['y'], $this->gps['x']);
      $this->gps['av'] = asin($this->gps['z'] / $this->gps['vl']);
    }

    return $this->gps[$name];
  }

  /** Distance between two coordinates.
  *
  * [1] ->distance(static $target)
  *    - Calculate distance to the target point.
  * [1] ->distance($x, $y, $z)
  *    - Calculate distance to the target point given as a set of separate coordinates.
  *
  * @param ?static|?numeric $target|$x the point to calculate distance from.
  * @param ?numeric $y the point to calculate distance from.
  * @param ?numeric $z the point to calculate distance from.
  * @return float distance
  */
  public function distance($target = null, $y = null, $z = null)
  {
    if(func_num_args() === 0 && isset($this->gps['vl']))
      return $this->gps['vl'];

    if($target instanceof self)
    {
      $_x = $this->x - $target->x;
      $_y = $this->y - $target->y;
      $_z = $this->z - $target->z;
    }
    else
    {
      $x = (float)$target;
      $y = (float)$y;
      $z = (float)$z;
      $_x = $this->x - $x;
      $_y = $this->y - $y;
      $_z = $this->z - $z;
    }

    $result = sqrt($_x*$_x + $_y*$_y + $_z*$_z);
    if(empty($x) && empty($y) && empty($z))
      $this->gps['vl'] = $result;

    return $result;
  }

  /** Translate coordinates in space (Point/Vector+distance)
  *
  * [1] ->translate(static $shift)
  *    - $shift is treated as a set of offsets to shift the origin by.
  * [2] ->translate(static $shift, $distance)
  *    - $shift is treated as a direction (vector) to move the the point a $distance into.
  *
  * @param self $shift the deltas or direction of translation.
  * @param ?numeric $distance the distance of translation.
  * @return self the translated coordinate
  */
  protected function transByPoint(Point $shift, $distance = null)
  {
    if(isset($distance))
    {
      if($distance == 0)
        return clone $this;

      return $this->transByPoint(static::fromPolar($distance, $shift->ap, $shift->av));
    }

    return static::fromCartesian(
      $this->x + $shift->x,
      $this->y + $shift->y,
      $this->z + $shift->z
    );
  }

  /** Translate coordinates in space (entry wrapper)
  *
  * [1] ->translate(static $shift[, $distance])
  *    - Offset the coordinates [by $distance, ]using $shift as offset vector.
  * [2] ->translate($x, $y, $z[, $distance])
  *    - Offset the coordinates of the point [by $distance, ]using specified separate deltas as offset vector.
  *
  * @param static|numeric $shift|$x the (set of) distance(s) by which a point must be translated.
  * @param ?numeric $distance|$y the distances by which a coordinate must be translated.
  * @param ?numeric $z the distances by which a coordinate must be translated.
  * @param ?numeric $distance the distances by which a coordinate must be translated.
  * @return self the translated coordinate
  */
  public function translate($x, $y = null, $z = null, $distance = null)
  {
    if($x instanceof self)
      return $this->transByPoint($x, $y);

    if(isset($distance))
      return $this->transByPoint(static::fromCartesian($x, $y, $z), $distance);

    return static::fromCartesian(
      $this->x + $x,
      $this->y + $y,
      $this->z + $z
    );
  }

  /** Translate coordinates in space (entry wrapper)
  *
  * [1] ->translate(static $shift[, $distance])
  *    - Offset the coordinates [by $distance, ]using $shift as offset vector.
  * [2] ->translate($x, $y, $z[, $distance])
  *    - Offset the coordinates of the point [by $distance, ]using specified separate deltas as offset vector.
  *
  * @param static|numeric $shift|$x the (set of) distance(s) by which a point must be translated.
  * @param ?numeric $distance|$y the distances by which a coordinate must be translated.
  * @param ?numeric $z the distances by which a coordinate must be translated.
  * @param ?numeric $distance the distances by which a coordinate must be translated.
  * @return self the translated coordinate
  */
  public function rotate($x, $y = null, $z = null)
  {
    if($x instanceof self)
      return static::fromPolar($this->vl, $this->ap + $x->ap, $this->av + $x->av);

    if(isset($z))
      return $this->rotate(static::fromCartesian($x, $y, $z));

    return static::fromPolar($this->vl, $this->ap + $x, $this->av + $y);
  }

  public function format($format = null, $decimals = null, $thousands = null)
  {
    if(!isset($format))
      return sprintf($this->format, $this->x, $this->y, $this->z);

    if(!isset($decimals))
      return sprintf($format, $this->x, $this->y, $this->z);

    return sprintf($format,
      number_format($this->x, $precision, $decimals ?: '.', $thousands),
      number_format($this->y, $precision, $decimals ?: '.', $thousands),
      number_format($this->z, $precision, $decimals ?: '.', $thousands)
    );
  }

// Magic!

  private function __construct($x, $y, $z, $polar = null)
  {
    if(!isset($x, $y, $z))
      throw new \InvalidArgumentException('Partial initialization disallowed.');

    if($polar)
    {
      $this->gps['vl'] = (float)$x;
      $this->gps['ap'] = (float)$y;
      $this->gps['av'] = (float)$z;
    }
    else
    {
      $this->gps['x'] = (float)$x;
      $this->gps['y'] = (float)$y;
      $this->gps['z'] = (float)$z;
    }
  }

  final public function __isset($name)
  {
    return array_key_exists($name, $this->gps);
  }

  final public function __get($name)
  {
    if(isset($this->gps[$name]))
      return $this->gps[$name];

    if(!array_key_exists($name, $this->gps))
      return $this->{"*$name"};

    if(isset($this->gps['x'], $this->gps['y'], $this->gps['z']))
      return $this->toPolar($name);

    if(isset($this->gps['vl'], $this->gps['ap'], $this->gps['av']))
      return $this->toCartesian($name);
  }

  final public function __toString()
  {
    return $this->format();
  }

  final public function __debugInfo()
  {
    return array('gps' => $this->gps, 'format' => $this->format);
  }

// \ArrayAccess implementation for coordinates.

  public function offsetExists($offset)
  {
    return $this->__isset($offset);
  }

  public function offsetGet($offset)
  {
    return $this->__get($offset);
  }

  final public function offsetSet($offset, $value)
  {
    throw new \LogicException('The class is immutable. Please create new object or use transformation functions.');
  }

  final public function offsetUnset($offset)
  {
    throw new \LogicException('The class is immutable. Please create new object or use transformation functions.');
  }
}
