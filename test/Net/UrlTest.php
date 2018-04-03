<?php

namespace AnrDaemon\Tests\Net;

use
  AnrDaemon\Net\Url,
  PHPUnit\Framework\TestCase;

final class UrlTest
extends TestCase
{
  protected $url;

  protected function _parse_str($string)
  {
    if(!is_string($string))
      return $string;

    parse_str($string, $query);
    return $query;
  }

  protected function _normalized_parts(array $parts)
  {
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

    return array_replace($blank, array_intersect_key($parts, $blank));
  }

  protected function getParts(Url $url)
  {
    $test = (function(){ return $this->params; })->bindTo($url, $url);
    return $test();
  }

  public function setUp()
  {
    $this->url = new Url('');
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
    $paths = [ null, '/', '/path', '/path/'];
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
          $this->_normalized_parts([
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
        $this->_normalized_parts([
          'query' => [$char => '1'],
        ]),
      ];
    }

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

  /** Test creation of an empty slate Url from empty URI
  *
  */
  public function testCreateEmptyClassFromEmptyString()
  {
    $this->assertTrue($this->_normalized_parts([]) === $this->getParts(new Url('')));
  }

  /** Test if parsing invalid URL throws exception.
  *
  * @expectedException \InvalidArgumentException
  * @depends testCreateEmptyClassFromEmptyString
  */
  public function testParseUrlWithException()
  {
    $this->url->parse('//:0');
  }

  /** Test parsing of obviously valid strings.
  *
  * @dataProvider validListProvider
  * @depends testCreateEmptyClassFromEmptyString
  */
  public function testParseValidUrl($url, $parts)
  {
    $this->assertTrue($parts === $this->getParts($this->url->parse($url)));
  }

  /** Test parsing of valid URL's mandgled by PHP's parse_str
  *
  * @see http://php.net/manual/en/language.variables.external.php Variables From External Sources
  * @dataProvider mangledCharsProvider
  * @depends testCreateEmptyClassFromEmptyString
  */
  public function testParseQueryString($url, $parts)
  {
    try
    {
      $this->assertTrue($parts === $this->getParts($this->url->parse($url)));
    }
    catch(\PHPUnit_Framework_ExpectationFailedException $e)
    {
      $key = array_keys($parts['query']);
      $key = reset($key);
      if(in_array($key, [' ', '.', '[']))
        return $this->markTestIncomplete('Mangled names of variables from external sources.');

      throw $e;
    }
  }

  /** Test scheme-port normalization for well-known protocols
  *
  * @dataProvider standardSchemesProvider
  * @depends testParseValidUrl
  */
  public function testOutputWellKnownSchemesNormalized($url, $result)
  {
    $this->assertTrue($result === (string) $this->url->parse($url));
  }
}
