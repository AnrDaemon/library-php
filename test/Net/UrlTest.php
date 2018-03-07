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

  protected function getParams(Url $url)
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
    $hosts = [ null, 'host', 'www.host.tld'];
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
          $value => [$value, [
            'scheme' => $scheme, //
            'user' => $user, //
            'pass' => $password, //
            'host' => $host, //
            'port' => (int)$port ?: null, // Null an empty port.
            'path' => $path, //
            'query' => $this->_parse_str($query), // Convert query string to array
            'fragment' => $fragment, //
          ]],
        ];
      }
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
    $this->assertTrue([
      'scheme' => null, // - e.g. http
      'user' => null, //
      'pass' => null, //
      'host' => null, //
      'port' => null, //
      'path' => null, //
      'query' => null, // - after the question mark ?
      'fragment' => null, // - after the hashmark #
    ] === $this->getParams(new Url('')));
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
  public function testParseValidUrl($url, $params)
  {
    $this->assertTrue($params === $this->getParams($this->url->parse($url)));
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
