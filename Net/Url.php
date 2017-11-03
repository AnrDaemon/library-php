<?php
/** URL handling class.
*
* @version SVN: $Id: Url.php 703 2017-11-03 00:36:44Z anrdaemon $
*/

namespace AnrDaemon\Net;

/** A class to simplify handling the various URL's
*
* The class is a read-only collection, the only way to modify its contents
* is to create a new instance of the class.
*
* The class is always trying to populate host/port and scheme upon creation. You may override
* them later on using {@see Url::setParts() self::setParts()}.
*
* When parsing the input URI or setting parts, empty values are stripped.
*
* URL parts can be accessed as properties (`$url->path`), query parts
* can be accessed as array indices (`$url['param']`).
*
* @property-read string $scheme Treatment of some well-known schemes (like http or ldap) is enhanced.
* @property-read string $user
* @property-read string $pass
* @property-read string $host The IDN hosts are decoded.
* @property-read int $port The port is always converted to integer.
* @property-read string $path Path always decoded, like `$_SERVER['DOCUMENT_URI']`.
* @property-read array $query Part after the question mark "?".
* @property-read string $fragment Part after the hashmark "#".
*/
class Url
implements \Iterator, \ArrayAccess, \Countable
{
  /**
  * @var array $defaultPorts The list of well-known schemes and associated ports.
  * @source 1 16 The current list of well-known schemes:
  */
  private static $defaultPorts = array(
    'ftp' => 21,
    'ftps' => 990,
    'gopher' => 70,
    'http'  => 80,
    'https' => 443,
    'imap' => 143,
    'imaps' => 993,
    'ldap' => 389,
    'ldaps' => 636,
    'nntp' => 119,
    'nntps' => 563,
    'pop3' => 110,
    'pop3s' => 995,
    'ssh' => 22,
    'telnet' => 23,
    'telnets' => 992,
  );

  /**
  * @var array $params Internal array holding the URI parts.
  * @source 1 8 The current list of well-known schemes:
  */
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

  /** @internal Recursive ksort
  * @param array &$array
  * @return void
  * @see \ksort()
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
  * Takes apart the `$url` and uses its parts to call {@see \AnrDaemon\Net\Url::setParts() self::setParts()}.
  *
  * The user, password and fragment fields are url-decoded.
  *
  * The IDN hostnames are decoded.
  *
  * The path is url-decoded, except for encoded "/"(%2F) character.
  *
  * The query string is decoded into an array by the extension of using
  * {@see \AnrDaemon\Net\Url::setParts() self::setParts()} to compose a new class instance.
  *
  * Note: May not parse all URL's in a desirable way.
  * See f.e. https://3v4l.org/BPsaa for mailto: URI's
  *
  * @see https://tools.ietf.org/html/rfc3986 [RFC3986]
  * @see \parse_url()
  * @see \parse_str()
  *
  * @uses \AnrDaemon\Net\Url::setParts() to construct resulting object.
  *
  * @param string $url An URL to parse.
  * @return Url A new class instance with corresponding parts replaced.
  */
  function parse($url)
  {
    // TODO: Correctly handle mailto: scheme https://3v4l.org/S0AIa mailto://user@example.org
    $parts = parse_url($url);
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
  * Note: This is a replacement, not merge; especially in case of a `query` part.
  *
  * Note: The `query` part is always decoded into an array.
  *
  * @param array $parts A set of parts to replace. Uses the same names parse_url uses.
  * @return Url A new class instance with corresponding parts replaced.
  */
  function setParts(array $parts)
  {
    /** Reset empty replacement parts to null
    *
    * Avoid creation of replacement array keys if they are not set.
    */
    foreach(array_keys($this->params) as $part)
      if(isset($parts[$part]) && empty($parts[$part]))
        $parts[$part] = null;

    if(isset($parts['port']))
      $parts['port'] = (int)$parts['port'];

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

      $this->rksort($query);
      $parts['query'] = $query;
    }

    $self = clone $this;
    $self->params = array_replace($this->params, array_intersect_key($parts, $this->params));
    return $self;
  }

// ArrayAccess

  /** @internal
  @see \ArrayAccess::offsetExists() */
  public function offsetExists($offset)
  {
    return isset($this->params['query'][$offset]);
  }

  /** @internal
  @see \ArrayAccess::offsetGet() */
  public function offsetGet($offset)
  {
    return $this->params['query'][$offset];
  }

  /** @internal
  @see \ArrayAccess::offsetSet() */
  public function offsetSet($offset, $value)
  {
    throw new \LogicException('Forbidden.');
  }

  /** @internal
  @see \ArrayAccess::offsetUnset() */
  public function offsetUnset($offset)
  {
    throw new \LogicException('Forbidden.');
  }

// Countable

  /** @internal
  @see \Countable::count() */
  public function count()
  {
    return empty($this->params['query']) ? 0 : count($this->params['query']);
  }

// Iterator

  /** @internal
  @see \Iterator::current() */
  public function current()
  {
    if(is_array($this->params['query']))
      return current($this->params['query']);
    else
      return null;
  }

  /** @internal
  @see \Iterator::key() */
  public function key()
  {
    if(is_array($this->params['query']))
      return key($this->params['query']);
    else
      return null;
  }

  /** @internal
  @see \Iterator::next() */
  public function next()
  {
    if(is_array($this->params['query']))
      next($this->params['query']);
  }

  /** @internal
  @see \Iterator::rewind() */
  public function rewind()
  {
    if(is_array($this->params['query']))
      reset($this->params['query']);
  }

  /** @internal
  @see \Iterator::valid() */
  public function valid()
  {
    return !is_null($this->key());
  }

// Magic!

  /** Create default instance of a self-reference URL.
  *
  * Try hard to discover the request scheme, server name and port.
  *
  * The server name is looked in `$_SERVER['SERVER_NAME']`, then in
  * `$_SERVER['HTTP_HOST']`, if not found.
  *
  * The server port is taken from `$_SERVER['SERVER_PORT']`, or if
  * `$_SERVER['HTTP_HOST']` is used to set the server name, the port
  * is looked in there as well.
  *
  * Hint: Provide an empty `$query` array to override any potential `$baseUrl` query part.
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

    if(isset($query))
    {
      $parts['query'] = $query;
    }

    $this->params = $this->setParts($parts)->params;
  }

  /** @internal */
  function __get($index)
  {
    return $this->params[$index];
  }

  /** @internal */
  function __isset($index)
  {
    return isset($this->params[$index]);
  }

  /** Converts URL to a sting representation.
  *
  * If URI scheme is specified, some well-known schemes are considered
  * and default port number is omitted from the resulting URI.
  *
  * @return string an URL-encoded string representation of the object.
  */
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

      if(isset($parts['port'], $parts['scheme'], $this::$defaultPorts[$parts['scheme']]))
        if($parts['port'] == $this::$defaultPorts[$parts['scheme']])
          unset($parts['port']);

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
