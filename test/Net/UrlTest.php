<?php

namespace AnrDaemon\Tests\Net;

use
  AnrDaemon\Net\Url,
  PHPUnit\Framework\TestCase;

final class UrlTest
extends TestCase
{
  protected static $url;

  protected function _parse_str($string)
  {
    if(!is_string($string))
      return $string;

    parse_str($string, $query);
    return $query;
  }

  protected function _normalized(array $parts)
  {
    static $set;
    static $blank = [
      'scheme' => null, // - e.g. http
      'user' => null, //
      'pass' => null, //
      'host' => null, //
      'port' => null, //
      'path' => null, //
      'query' => null, // - after the question mark ?
      'fragment' => null, // - after the hashmark #
    ];

    if(!isset($set))
    {
      $url = new Url('');
      $set = function(array $parts)
      {
         $self = clone $this;
         $self->params = $parts;
         return $self;
      };
      $set = $set->bindTo($url, $url);
    }

    // Keep parts in static order
    return $set(array_replace($blank, array_intersect_key($parts, $blank)));
  }

  /** @beforeClass */
  public static function setUrl()
  {
    static::$url = new Url('');
  }

  /** Provide a sample valid URI
  */
  public function sampleUrl()
  {
    return "//localhost/?x=y";
  }

  protected function getParts(Url $url)
  {
    $test = function() { return $this->params; };
    $test = $test->bindTo($url, $url);
    return $test();
  }

  /** Provide a list of valid strings with no known parsing caveats
  */
  public function validListProvider()
  {
    $schemes = [ null, 'http'];
    $users = [ null, 'user'];
    $passs = [ null, 'password'];
    $hosts = [ null, 'localhost', 'www.example.org'];
    $ports = [ null, 8080];
    $paths = [ null, '/', '/path', '/path/', '/path+/'];
    $querys = [ null, 'query=string'];
    $fragments = [ null, 'fragment'];

    $data = [];

    foreach($schemes as $scheme)
    foreach($users as $user)
    foreach($passs as $password)
    foreach($hosts as $host)
    foreach($ports as $port)
    foreach($paths as $path)
    foreach($querys as $query)
    foreach($fragments as $fragment)
    {
      $value = '';

      if(isset($scheme))
      {
        $value .= "$scheme:";
      }

      if(isset($host))
      {
        $value .= "//";

        if(isset($user))
        {
          $value .= $user;

          if(isset($password))
          {
            $value .= ":$password";
          }

          $value .= "@";
        }

        $value .= $host;

        if(isset($port))
        {
          $value .= ":$port";
        }
      }

      if(isset($path))
      {
        $value .= $path;
      }

      if(isset($query))
      {
        $value .= "?$query";
      }

      if(isset($fragment))
      {
        $value .= "#$fragment";
      }

      if($value)
      {
        $data += [
          $value => [$value,
          $this->_normalized([
            'scheme' => $scheme, //
            'user' => $user, //
            'pass' => $password, //
            'host' => $host, //
            'port' => (int)$port ?: null, // Null an empty port.
            'path' => $path, //
            'query' => $this->_parse_str($query), // Convert query string to array
            'fragment' => $fragment, //
          ])],
        ];
      }
    }

    return $data;
  }

  /** Provide a list of potentially mangled characters
  */
  public function mangledCharsProvider()
  {
    $list = array_diff(range(' ', '~'), range('0', '9'), range('A', 'Z'), range('a', 'z'), ['&', ';']);
    $data = [];
    foreach($list as $char)
    {
      $name = "?" . urlencode($char) . "=1";
      $data["'{$name}' ($char)"] = [
        $name,
        $this->_normalized([
          'query' => [$char => '1'],
        ]),
      ];
    }

    return $data;
  }

  /** Provide _SERVER overrides for Url::fromHttp() method.
  *
  * Flags in test names (capital - present _SERVER keys, small - absent):
  *   H - HTTP_HOST
  *   X - X-Forwarded-*
  *   +/- to trust X-Forwarded headers or not
  *   S - REQUEST_SCHEME
  *   U - REQUEST_URI (+ - contains extra information, like f.e. server name)
  *   N - SERVER_NAME
  *   P - SERVER_PORT
  */
  public function environmentProvider()
  {
    $data["(from X-Forwarded- headers (HX+SUNP))"] = [[
      "HTTP_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PROTO" => "https",
      "HTTP_X_FORWARDED_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PORT" => 80,
      "REQUEST_SCHEME" => "http",
      "REQUEST_URI" => "/",
      "SERVER_NAME" => "upstream.example.org",
      "SERVER_PORT" => 8080,
      "trust" => true, // To trust X-Forwarded-* headers or not.
    ], $this->_normalized([
      "scheme" => "https",
      "host" => "real.example.org",
      "port" => 80,
      "path" => "/",
    ])];

    $data["(from server data (HX-SUNP))"] = [[
      "HTTP_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PROTO" => "https",
      "HTTP_X_FORWARDED_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PORT" => 80,
      "REQUEST_SCHEME" => "http",
      "REQUEST_URI" => "/",
      "SERVER_NAME" => "upstream.example.org",
      "SERVER_PORT" => 8080,
      "trust" => false, // To trust X-Forwarded-* headers or not.
    ], $this->_normalized([
      "scheme" => "http",
      "host" => "upstream.example.org",
      "port" => 8080,
      "path" => "/",
    ])];

    $data["(from request line (HX-SU+NP))"] = [[
      "HTTP_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PROTO" => "https",
      "HTTP_X_FORWARDED_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PORT" => 80,
      "REQUEST_SCHEME" => "http",
      "REQUEST_URI" => "//real.example.org/",
      "SERVER_NAME" => "upstream.example.org",
      "SERVER_PORT" => 8080,
      "trust" => false, // To trust X-Forwarded-* headers or not.
    ], $this->_normalized([
      "scheme" => "http",
      "host" => "real.example.org",
      "port" => 8080,
      "path" => "/",
    ])];

    $data["(from server data / no server name (HX-sUNP))"] = [[
      "HTTP_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PROTO" => "https",
      "HTTP_X_FORWARDED_HOST" => "real.example.org",
      "HTTP_X_FORWARDED_PORT" => 80,
      "REQUEST_SCHEME" => "http",
      "REQUEST_URI" => "/",
      "SERVER_NAME" => "",
      "SERVER_PORT" => 8080,
      "trust" => false, // To trust X-Forwarded-* headers or not.
    ], $this->_normalized([
      "scheme" => "http",
      "host" => "real.example.org",
      "port" => 8080,
      "path" => "/",
    ])];

    return $data;
  }

  public function overridePartsProvider()
  {
    $parts = [
      "scheme" => "http",
      "user" => "user",
      "pass" => "password",
      "host" => "localhost",
      "port" => 80,
      "path" => "/",
      "query" => ["x" => "y"],
      "fragment" => "fragment",
    ];

    foreach($parts as $name => $value)
    {
      $data[$name] = [
        [$name => $value],
        $this->_normalized([$name => $value]),
      ];
    }

    $data["query(as string)"] = [
      ["query" => "query=string"],
      $this->_normalized(["query" => ["query" => "string"]]),
    ];

    $data["in a batch"] = [
      [
        "scheme" => "http",
        "host" => "localhost",
        "path" => "/",
      ],
      $this->_normalized([
        "scheme" => "http",
        "host" => "localhost",
        "path" => "/",
      ]),
    ];

    return $data;
  }

  /** Provide a list of well-known schemes and ports
  */
  public function standardSchemesProvider()
  {
    $list = array(
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

    $data = [];
    foreach($list as $scheme => $port)
    {
      $data += ["$scheme ($port)" => [
        "$scheme://host:$port",
        "$scheme://host",
      ]];
    }

    return $data;
  }

  /** Test creation of an empty slate Url from null
  *
  * Test if legacy code is affecting the class behavior.
  */
  public function testCreateEmptyEntityFromNull()
  {
    $_SERVER["SERVER_NAME"] = "localhost";
    $this->assertEquals($this->_normalized([]), new Url);
  }

  /** Test creation of an empty slate Url from empty URI
  *
  */
  public function testCreateEmptyEntityFromEmptyString()
  {
    $this->assertEquals($this->_normalized([]), new Url(''));
  }

  /** Test creation of an Url from URI
  *
  */
  public function testCreateEntityFromUri()
  {
    $this->assertEquals($this->_normalized([
      "host" => "localhost",
      "path" => "/",
      "query" => ["x" => "y"],
    ]), new Url($this->sampleUrl()));
  }

  /** Test creation of an Url with query override
  *
  */
  public function testCreateEntityWithQueryClearing()
  {
    $this->assertEquals($this->_normalized([
      "host" => "localhost",
      "path" => "/",
    ]), new Url($this->sampleUrl(), []));
  }

  /** Test creation of an Url with query override
  *
  */
  public function testCreateEntityWithQueryOverride()
  {
    $this->assertEquals($this->_normalized([
      "host" => "localhost",
      "path" => "/",
      "query" => ["z" => "t"],
    ]), new Url($this->sampleUrl(), ["z" => "t"]));
  }

  /** Test creation of entity from environment
  *
  * @dataProvider environmentProvider
  * @depends testCreateEmptyEntityFromEmptyString
  */
  public function testCreateEntityFromEnvironment($env, $parts)
  {
    $_SERVER = $env + $_SERVER;
    $this->assertEquals($parts, Url::fromHttp([], $_SERVER["trust"]));
  }

  /** Test if parsing invalid URL throws exception.
  *
  * @expectedException \InvalidArgumentException
  * @depends testCreateEmptyEntityFromEmptyString
  */
  public function testParseUrlWithException()
  {
    static::$url->parse('//:0');
  }

  /** Test parsing of obviously valid strings.
  *
  * @dataProvider validListProvider
  * @depends testCreateEmptyEntityFromEmptyString
  */
  public function testParseValidUrl($url, $parts)
  {
    $this->assertEquals($parts, static::$url->parse($url));
  }

  /** Test parsing of valid URL's mandgled by PHP's parse_str
  *
  * @see http://php.net/manual/en/language.variables.external.php Variables From External Sources
  * @dataProvider mangledCharsProvider
  * @depends testCreateEmptyEntityFromEmptyString
  */
  public function testParseQueryString($url, $parts)
  {
    try
    {
      $this->assertEquals($parts, static::$url->parse($url));
    }
    catch(\PHPUnit_Framework_ExpectationFailedException $e)
    {
      $key = array_keys($parts->query);
      $key = reset($key);
      if(in_array($key, [' ', '.', '[']))
        return $this->markTestIncomplete('Mangled names of variables from external sources.');

      throw $e;
    }
    catch(\PHPUnit\Framework\ExpectationFailedException $e)
    {
      $key = array_keys($parts->query);
      $key = reset($key);
      if(in_array($key, [' ', '.', '[']))
        return $this->markTestIncomplete('Mangled names of variables from external sources.');

      throw $e;
    }
  }

  /** Test setting various URL parts
  *
  * @dataProvider overridePartsProvider
  * @depends testCreateEmptyEntityFromEmptyString
  */
  public function testSetUrlPart($overrides, $parts)
  {
    $this->assertEquals($parts, static::$url->setParts($overrides));
  }

  /** Test scheme-port normalization for well-known protocols
  *
  * @dataProvider standardSchemesProvider
  * @depends testParseValidUrl
  */
  public function testOutputWellKnownSchemesNormalized($url, $result)
  {
    $this->assertTrue($result === (string) static::$url->parse($url));
  }
}
