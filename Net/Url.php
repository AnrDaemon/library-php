<?php
/** URL handling class.
*
* @version SVN: $Id: Url.php 697 2017-11-01 17:05:47Z anrdaemon $
*/

namespace AnrDaemon\Misc;

/** A class to simplify handling the various URL's
*
* The class is read-only collection, the only way to modify its contents
* is to create a new instance of the class.
*
* The class is always trying to populate host and scheme
*/
class Url
implements \Iterator, \ArrayAccess, \Countable
{
  protected $params = array(
    'scheme' => null, // - e.g. http
    'user' => null, //
    'pass' => null, //
    'host' => null, //
    'port' => null, //
    'path' => null, //
    'query' => null, // - after the question mark ?
    'fragment' => null, // - after the hashmark #
  );

  /** Internal: recursive ksort
  * @param array &$array
  * @return void
  * @see \ksort
  */
  protected function rksort(&$array)
  {
    if(is_array($array))
    {
      ksort($array);
      array_walk($array, array($this, __FUNCTION__));
    }
  }

  /** Parse an URL into replacement parts and create a new class instance using them
  *
  * Takes apart the $url and uses its parts to call self::setParts().
  *
  * The user, password and fragment fields are url-decoded.
  *
  * The IDN hostnames are decoded.
  *
  * The path is url-decoded, except for encoded "/"(%2F) character.
  *
  * The query string is decoded into an array by the extension of using
  * self::setParts() to compose a new class instance.
  *
  * Note: May not parse all URL's in a desirable way.
  * See f.e. https://3v4l.org/BPsaa for mailto: URI's
  *
  * @see RFC:3986
  * @see self::setParts
  * @see \parse_url
  * @see \parse_str
  * @param string $url An URL to parse.
  * @return self A new class instance with corresponding parts replaced.
  */
  function parse($string)
  {
    // TODO: Correctly handle mailto: scheme https://3v4l.org/S0AIa mailto://user@example.org
    $parts = parse_url($string);
    if($parts === false)
      throw new \InvalidArgumentException("Provided string can not be parsed as valid URL.");

    foreach(array('user', 'pass', 'fragment') as $part)
      if(isset($parts[$part]))
        $parts[$part] = urldecode($parts[$part]);

    if(isset($parts['host']))
      $parts['host'] = idn_to_utf8($parts['host']);

    // Force port to be numeric.
    // If it would fail to convert (converts to zero), self::setParts() will strip it.
    if(isset($parts['port']))
      $parts['port'] = (int)$parts['port'];

    if(isset($parts['path']))
      $parts['path'] = urldecode(str_ireplace('%2F', '%252F', $parts['path']));

    return $this->setParts($parts);
  }

  /** Create a new instance of the class by replacing parts in the current instance
  *
  * Note: This is a replacement, not merge; especially in case of a 'query' part.
  *
  * Note: The 'query' part is always decoded into an array.
  *
  * @param array $parts A set of parts to replace. Uses the same names parse_url uses.
  * @return self A new class instance with corresponding parts replaced.
  */
  function setParts(array $parts)
  {
    foreach(array('scheme', 'port', 'user', 'pass', 'path', 'query', 'fragment') as $part)
      // Avoid creation of array keys if they are not set.
      if(isset($parts[$part]) && empty($parts[$part]))
        $parts[$part] = null;

    if(isset($parts['port']))
      $parts['port'] = (int)$parts['port'];

    if(isset($parts['query']))
    {
      if(!is_array($parts['query']))
      {
        parse_str($parts['query'], $query);
      }
      else
      {
        $query = $parts['query'];
      }

      $this->rksort($query);
      $parts['query'] = $query;
    }

    $self = clone $this;
    $self->params = array_replace($this->params, array_intersect_key($parts, $this->params));
    return $self;
  }

// ArrayAccess

  public function offsetExists($offset)
  {
    return isset($this->params['query'][$offset]);
  }

  public function offsetGet($offset)
  {
    return $this->params['query'][$offset];
  }

  public function offsetSet($offset, $value)
  {
    throw new \LogicException('Forbidden.');
  }

  public function offsetUnset($offset)
  {
    throw new \LogicException('Forbidden.');
  }

// Countable

  public function count()
  {
    return empty($this->params['query']) ? 0 : count($this->params['query']);
  }

// Iterator

  public function current()
  {
    if(is_array($this->params['query']))
      return current($this->params['query']);
    else
      return null;
  }

  public function key()
  {
    if(is_array($this->params['query']))
      return key($this->params['query']);
    else
      return null;
  }

  public function next()
  {
    if(is_array($this->params['query']))
      next($this->params['query']);
  }

  public function rewind()
  {
    if(is_array($this->params['query']))
      reset($this->params['query']);
  }

  public function valid()
  {
    return !is_null($this->key());
  }

// Magic!

  /** Create default instance of a self-reference URL.
  *
  * Try hard to discover the request scheme, server name and port.
  *
  * The server name is looked in $_SERVER['SERVER_NAME'], then in
  * $_SERVER['HTTP_HOST'], if not found.
  *
  * The server port is taken from $_SERVER['SERVER_PORT'], or if
  * $_SERVER['HTTP_HOST'] is used to set the server name, the port is looked in there as well.
  *
  * If request scheme is http or https, the default port is then stripped from the input.
  *
  * Hint: Provide an empty $query array to override any potential $baseUrl query part.
  *
  * @param ?string $baseUrl An optional initial URL to set defaults from.
  * @param ?array $query An optional query key-value pairs.
  */
  function __construct($baseUrl = null, array $query = null)
  {
    $parts = isset($baseUrl) ? $this->parse($baseUrl)->params : array();

    foreach(array(
      'scheme' => "REQUEST_SCHEME",
      'host' => 'SERVER_NAME',
      'port' => 'SERVER_PORT'
    ) as $key => $header)
    {
      if(empty($parts[$key]))
      {
        $parts[$key] = empty($_SERVER[$header]) ? null : $_SERVER[$header];
      }
    }

    if(empty($parts['host']) && !empty($_SERVER['HTTP_HOST']))
    {
      $fwd = parse_url("//{$_SERVER['HTTP_HOST']}");

      if(isset($fwd['host']))
      {
        $parts['host'] = $fwd['host'];
        if(isset($fwd['port']))
        {
          $parts['port'] = $fwd['port'];
        }
      }
    }

    if(
      ($parts['scheme'] === 'http' && $parts['port'] == 80) ||
      ($parts['scheme'] === 'https' && $parts['port'] == 443)
    )
    {
      $parts['port'] = null;
    }

    if(isset($query))
    {
      $parts['query'] = $query;
    }

    $this->params = $this->setParts($parts)->params;
  }

  function __get($index)
  {
    return $this->params[$index];
  }

  function __isset($index)
  {
    return isset($this->params[$index]);
  }

  function __toString()
  {
    $parts = $this->params;
    $result = '';

    if(isset($parts['scheme']))
      $result .= $parts['scheme'] . ":";

    if(isset($parts['host']))
    {
      $result .= "//";

      if(isset($parts['user']))
      {
        $result .= rawurlencode($parts['user']);

        if(isset($parts['pass']))
          $result .= ":" . rawurlencode($parts['pass']);

        $result .= "@";
      }

      $result .= idn_to_ascii($parts['host']);

      if(isset($parts['port']))
        $result .= ":" . $parts['port'];
    }

    if(isset($parts['path']))
    {
      if(isset($parts['host']) && $parts['path'][0] !== '/')
        throw new \UnexpectedValueException("Host is set but path is not absolute; unable to convert to string");

      $path = explode('%2F', $parts['path']);
      $result .= implode('%2F', array_map(function($part){
        /*
          BUG?: paths containing, f.e., "@" are encoded.
          For future reference:
          https://tools.ietf.org/html/rfc3986#section-2.2
        */
        return implode('/', array_map('rawurlencode', explode('/', $part)));
      }, $path));
    }

    if(isset($parts['query']))
    {
      if(is_array($parts['query']))
      {
        $query = $parts['query'];
      }
      else
      {
        parse_str($parts['query'], $query);
      }
      $result .= "?" . http_build_query($query);
    }

    if(isset($parts['fragment']))
      $result .= "#" . rawurlencode($parts['fragment']);

    return $result;
  }
}
