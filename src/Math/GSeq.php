<?php

namespace AnrDaemon\Math;

/** Geometric sequence class
*
* General usage idea is that you create a new sequence using either base and
* multiplier, or multiplier, number of elements and a known sum of them.
*
* Then you can seek/iterate them as needed, or retrieve specific element via
* simple function call.
*/
class GSeq
implements \SeekableIterator
{
  protected $b, $q, $n;

  protected static function ensureValid($n, $message = null)
  {
    if($n < 1 || !is_int($n))
      throw new \OutOfBoundsException($message ?: 'Sequence number should be integer starting with 1.');
  }

  /** Create new geometric sequence...
  *
  * [1] create($b, $q)
  *       - from base and multiplier;
  * [2] create($sum, $q, $n)
  *       - from sum, multiplier and number of elements.
  */
  public static function create($b, $q, $n = 1)
  {
    if($n == 1)
      return new static($b, $q);

    static::ensureValid($n, "Amount of elements must be an integer number bigger than zero.");

    return new static($b * (1 - $q) / (1 - pow($q, $n)), $q);
  }

  /** Calculate sum of N consequent characters.
  *
  * Either N first, or N after first M.
  *
  * @param int $n number of elements to sum up
  * @param ?int $m number of elements to skip
  * @return float the sum of elements
  */
  public function sum($n, $m = 0)
  {
    if($m > 0)
      return $this->sum($n+$m) - $this->sum($m);

    static::ensureValid($n);

    return $this->b * (1 - pow($this->q, $n)) / (1 - $this->q);
  }

// Magic!

  private function __construct($b, $q)
  {
    $this->b = $b;
    $this->q = $q;
    $this->n = 1;
  }

  public function __invoke($n)
  {
    if($n == 1)
      return $this->b;

    static::ensureValid($n);

    return $this->b * pow($this->q, (int)$n - 1);
  }

// SeekableIterator

  public function seek($n)
  {
    static::ensureValid($n);

    $this->n = (int)$n;
  }

// Iterator

  public function valid()
  {
    return $this->n > 0;
  }

  public function current()
  {
    return $this->__invoke($this->n);
  }

  public function key()
  {
    return $this->n;
  }

  public function next()
  {
    $this->n++;
  }

  public function rewind()
  {
    $this->seek(1);
  }
}
