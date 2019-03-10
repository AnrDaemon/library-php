<?php
/** URL handling class.
*
* @version SVN: $Id: Url.php 909 2018-10-17 13:21:08Z anrdaemon $
*/

namespace AnrDaemon\Net;

/** A class to simplify handling the various URL's
*
* The class is a read-only collection, the only way to modify its contents
* is to create a new instance of the class.
*
* URL parts can be accessed as properties (`$url->path`), query parts (beside
* obvious `$url->query['param']`) can also be accessed as array indices
* (`$url['param']`) for convenience.
*
* When parsing the URI or setting parts, empty values are stripped.
*
* In case you want to create a website self-reference URL, the named
* constructor {@see \AnrDaemon\Net\Url::fromHttp self::fromHttp} is provided.
*
* It will try to populate scheme/host/port/path/query parts from information
* available in $_SERVER array.
*
* Warning: PHP compat: PHP ({@see \parse_str parse_str()}) converts certain characters in request variable names.
* See Note after Example 3 on http://php.net/variables.external#example-88
*
* @property-read string $scheme Treatment of some well-known schemes (like http or ldap) is enhanced.
* @property-read string $user
* @property-read string $pass
* @property-read string $host The IDN hosts are decoded.
* @property-read int $port The port is always converted to integer.
* @property-read string $path Path, always decoded, like in `$_SERVER['DOCUMENT_URI']`.
* @property-read array $query Part after the question mark "?".
* @property-read string $fragment Part after the hashmark "#".
*/
class Url
implements \IteratorAggregate, \ArrayAccess, \Countable
{
  /**
  * @var array $defaultPorts The list of well-known schemes and associated ports.
  * @source 1 16 The current list of well-known schemes:
  */
  protected static $defaultPorts = array(
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
  * @source 1 8 Parts list:
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

  /** @var string Cached representation of this object. */
  protected $uri;

  /** @internal Parse URL and throw exception on error.
  * @param string $url
  * @return array
  * @see \parse_url()
  */
  protected function _parse_url($url)
  {
    // TODO: Correctly handle mailto: scheme https://3v4l.org/S0AIa mailto://user@example.org
    $parts = parse_url($url);
    if($parts === false)
      throw new \InvalidArgumentException("Provided argument can not be parsed as valid URL.");

    return $parts;
  }

  /** @internal Parse query string and return resulting array.
  *
  * If input is not a string, the input returned unmodified.
  *
  * @param string $string
  * @return array
  * @see \parse_str()
  */
  protected function _parse_str($string)
  {
    if(is_array($string))
      return $string;
    // TODO:query strtok($query, ini_get('arg_separator.input'));
    parse_str($string, $query);
    return $query;
  }

  /** @internal Recursive ksort
  * @param array &$array
  * @return void
  * @see \ksort()
  */
  protected function _rksort(&$array)
  {
    if(is_array($array))
    {
      ksort($array);
      array_walk($array, array($this, __FUNCTION__));
    }
  }

  /** Creates Url instance from HTTP request metadata, such as $_SERVER[REQUEST_*] and $_SERVER[HTTP_*] variables.
  *
  * Function uses data available in the request environment to construct the so-called self-reference.
  *
  * The order in which environment is processed is as follows:
  *
  *  1. X-Forwarded-* headers, if trusted.
  *  2. The entire REQUEST_URI variable as set by SAPI.
  *  3. REQUEST_SCHEME / SERVER_NAME / SERVER_PORT variables from SAPI.
  *  4. The Host header in case host part was not set yet.
  *
  * If $trust_x_forwarded_headers is true (the default), the following headers are considered:
  *
  *  - X-Forwarded-Proto - original protocol the request received with (f.e. https);
  *  - X-Forwarded-Port - original receiving port;
  *  - X-Forwarded-Host - host (and possible port, if not set by X-Forwarded-Port) the original request received for.
  *
  * Warning: It is stronly recommended to configure calling webserver to override X-Forwarded-* headers an application
  * should not trust.
  *
  * Warning: Application should receive an already sanitized environment it could rely upon.
  *
  * @param array $override An array of parts to override. {@see \AnrDaemon\Net\Url::setParts() self::setParts()}
  * @param bool $trust_x_forwarded_headers To trust the X-Forwarded-{Host,Port,Proto,etc.} headers or not.
  * @return Url A new class instance.
  */
  public static function fromHttp(array $overrides = [], $trust_x_forwarded_headers = true)
  {
    $self = new static("");
    $parts = [];

    if($trust_x_forwarded_headers)
    {
      foreach(array(
        'scheme' => "HTTP_X_FORWARDED_PROTO",
        'port' => "HTTP_X_FORWARDED_PORT",
      ) as $key => $header)
      {
        if(!empty($_SERVER[$header]))
        {
          $parts += [$key => $_SERVER[$header]];
        }
      }

      if(!empty($_SERVER["HTTP_X_FORWARDED_HOST"]))
      {
        $parts += $self->_parse_url("//{$_SERVER["HTTP_X_FORWARDED_HOST"]}");
      }
    }

    if(!empty($_SERVER["REQUEST_URI"]))
    {
      $parts += $self->_parse_url($_SERVER["REQUEST_URI"]);
    }

    foreach(array(
      'scheme' => "REQUEST_SCHEME",
      'host' => "SERVER_NAME",
      'port' => "SERVER_PORT",
    ) as $key => $header)
    {
      if(!isset($parts[$key]) && !empty($_SERVER[$header]))
      {
        $parts += [$key => $_SERVER[$header]];
      }
    }

    /** Parse Host: header as host[:port] */
    if(empty($parts['host']) && !empty($_SERVER['HTTP_HOST']))
    {
      $parts += $self->_parse_url("//{$_SERVER['HTTP_HOST']}");
    }

    if(!empty($parts['host']))
    {
      $parts['host'] = idn_to_utf8($parts['host'], 0, INTL_IDNA_VARIANT_UTS46);
    }

    return $self->setParts($overrides + $parts);
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
  public function parse($url)
  {
    $parts = $this->_parse_url($url);

    foreach(array('user', 'pass', 'fragment') as $part)
      if(isset($parts[$part]))
        $parts[$part] = urldecode($parts[$part]);

    if(isset($parts['host']))
      $parts['host'] = idn_to_utf8($parts['host']);

    if(isset($parts['path']))
      $parts['path'] = rawurldecode(str_ireplace('%2F', '%252F', $parts['path']));

    return $this->setParts($parts);
  }

  /** Create a new instance of the class by replacing parts in the current instance
  *
  * Note: This is a replacement, not merge; especially in case of a `query` part.
  *
  * Note: The `query` part is always decoded into an array.
  *
  * @param array $parts A set of parts to replace. Uses the same names parse_url uses.
  * @return \AnrDaemon\Net\Url A new class instance with corresponding parts replaced.
  */
  public function setParts(array $parts)
  {
    /** Filter input array */
    $parts = array_intersect_key($parts, $this->params);

    /** Force port to be numeric.
    *
    * If it would fail to convert (converts to zero), we will strip it.
    */
    if(isset($parts['port']))
      $parts['port'] = (int)$parts['port'];

    /** Convert query string to ksort()'ed array */
    if(isset($parts['query']))
    {
      $query = $this->_parse_str($parts['query']);
      $this->_rksort($query);
      $parts['query'] = $query;
    }

    /** Reset empty replacement parts to null
    *
    * This simplifies later checks for existence of URL parts.
    */
    array_walk(
      $parts,
      function(&$part, $key)
      {
        if(is_string($part))
        { /** Use strlen() instead of empty() to account for possible "0" values
          *
          * Valid case for (f.e.) user/pass parts.
          */
          $part = strlen($part) ? $part : null;
        }
        else
        {
          $part = $part ?: null;
        }
      }
    );

    $self = clone $this;
    // Keep URL parts in the same order at all times.
    $self->params = array_replace($this->params, $parts);
    return $self;
  }

// Magic!

  /** Create a representation of an URL.
  *
  * Without parameters, the class creates an "empty" URL.
  *
  * If base URL is specified, it will be used as template to fill parts.
  *
  * Hint: Provide an empty `$query` array to override any potential base URL query part.
  *
  * @param ?string $baseUrl An optional base URL to set defaults from.
  * @param ?array $query An optional query key-value pairs.
  */
  public function __construct($baseUrl = null, array $query = null)
  {
    if(!isset($baseUrl) || $baseUrl === '')
      return;

    $self = $this->parse($baseUrl);

    if(is_array($query))
    {
      $self = $self->setParts(['query' => $query]);
    }

    $this->params = $self->params;
  }

  /** @internal */
  public function __clone()
  {
    $this->uri = null;
  }

  /** @internal */
  public function __get($index)
  {
    return $this->params[$index];
  }

  /** @internal */
  public function __isset($index)
  {
    return isset($this->params[$index]);
  }

  /** Converts URL to a string representation.
  *
  * If URI scheme is specified, some well-known schemes are considered
  * and default port number is omitted from the resulting URI.
  *
  * @return string an URL-encoded string representation of the object.
  */
  public function __toString()
  {
    if($this->uri)
      return $this->uri;

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

      if(isset($parts['port'], $parts['scheme'], self::$defaultPorts[$parts['scheme']]))
        if($parts['port'] == self::$defaultPorts[$parts['scheme']])
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

    return $this->uri = $result;
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

// IteratorAggregate

  /** @internal
  @see \IteratorAggregate::getIterator() */
  public function getIterator()
  {
    return new \ArrayIterator($this->params['query']);
  }
}
