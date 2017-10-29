<?php
/** URL handling class.
*
* @version SVN: $Id: Url.php 694 2017-10-29 16:44:16Z anrdaemon $
*/

namespace AnrDaemon\Misc;

class Url
implements \Iterator, \ArrayAccess, \Countable
{
  protected $params = array(
    'scheme' => null, // - e.g. http
    'host' => null, //
    'port' => null, //
    'user' => null, //
    'pass' => null, //
    'path' => null, //
    'query' => null, // - after the question mark ?
    'fragment' => null, // - after the hashmark #
  );

  protected function rksort(&$array)
  {
    if(is_array($array))
    {
      ksort($array);
      array_walk($array, array($this, __FUNCTION__));
    }
  }

  function parse($string)
  {
    // TODO: Correctly handle mailto: scheme https://3v4l.org/S0AIa mailto://user@example.org
    $parts = parse_url($string);
    if($parts === false)
      throw new \InvalidArgumentException("Provided string can not be parsed as valid URL.");

    if(isset($parts['path']))
      $parts['path'] = urldecode(str_ireplace('%2F', '%252F', $parts['path']));

    return $this->setParts($parts);
  }

  function setParts(array $parts)
  {
    foreach(array('scheme', 'port', 'user', 'pass', 'path', 'query', 'fragment') as $part)
      if(isset($parts[$part]) && empty($parts[$part]))
        $parts[$part] = null;

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

  function __construct($baseUrl = null, array $query = null)
  {
    if(isset($baseUrl))
    {
      $parts = $this->parse($baseUrl)->params;
    }
    else
    {
      $parts = array();
    }

    foreach(array('scheme' => "REQUEST_SCHEME", 'host' => 'SERVER_NAME', 'port' => 'SERVER_PORT'
    ) as $key => $header)
    {
      if(empty($parts[$key]))
      {
        $$key = empty($_SERVER[$header]) ? null : $_SERVER[$header];
      }
      else
      {
        $$key = $parts[$key];
      }
    }

    if(($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443))
    {
      $port = null;
    }

    $parts = array_replace($parts, array('scheme' => $scheme, 'host' => $host, 'port' => $port));

    if(isset($query))
    {
      $parts = array_replace($parts, array('query' => $query));
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
      $result .= "//";

    if(isset($parts['user']))
      $result .= rawurlencode($parts['user']);

    if(isset($parts['pass']))
      $result .= ":" . rawurlencode($parts['pass']);

    if(isset($parts['user']))
      $result .= "@";

    if(isset($parts['host']))
      $result .= idn_to_ascii($parts['host']);

    if(isset($parts['port']))
      $result .= ":" . $parts['port'];

    if(isset($parts['path']))
    {
      $path = explode('%2F', $parts['path']);
      $result .= implode('%2F', array_map(function($part){
        /*
          BUG: paths containing, f.e., "@" are encoded.
          For future reference:
          https://tools.ietf.org/html/rfc3986#section-2.2
        */
        return str_replace('%2F', '/', rawurlencode($part));
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
