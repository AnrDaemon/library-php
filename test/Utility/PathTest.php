<?php

namespace AnrDaemon\Tests\Utility;

use AnrDaemon\Utility\Path;
use PHPUnit\Framework\TestCase;

final class PathTest
extends TestCase
{
  public function defaultPairsProvider()
  {
    $data = [
      "empty path" => array("", ""),
      "root" => array('/', '/'),
      "relative" => array("foo", "foo"),
      "dot dir" => array('/foo/bar', '/foo/./bar'),
      "absolute path inescapable" => array('/', '/Foo/Bar/../../../..'),
      "multi slash #1" => array('/', '//'),
      "multi slash #2" => array('/', '///'),
      "multi slash #3" => array('/Foo', '///Foo'),
      "otherdir" => array('/bar', '/foo/../bar/'),
      "windows disk only" => array("D:", "D:"),
      "windows disk root" => array('c:/', 'c:\\'),
      "windows disk relative" => array("e:g", "e:g"),
      "windows multi slash #1" => array('c:/foo/bar', 'c:/foo//bar'),
      "windows multi slash #2" => array('C:/foo/bar', 'C://foo//bar'),
      "windows multi slash #3" => array('C:/foo/bar', 'C:///foo//bar'),
      "windows otherdir" => array('C:/bar', 'C:/foo/../bar'),
    ];

    return $data;
  }

  public function escapablePairsProvider()
  {
    $data = [
      "simple" => array('../foo', '../foo'),
      "simple otherdir" => array('../bar', '../foo/../bar'),
      "chained escape" => array("../../bar", "a/../../b/../../bar"),
      "double collapse" => array('../src', 'Foo/Bar/../../../src'),
      "windows otherdir" => array('c:../b', 'c:.\\..\\a\\..\\b'),
    ];

    return $data;
  }

  /** Test normalization of standard pairs
  *
  * @dataProvider defaultPairsProvider
  */
  public function testNormalizeStandardPair($target, $path)
  {
    $this->assertTrue($target === Path::normalize($path, null, "/"));
  }

  /** Test normalization of escapable pairs
  *
  * @dataProvider escapablePairsProvider
  */
  public function testNormalizeEscapingPair($target, $path)
  {
    $this->assertTrue($target === Path::normalize($path, true, "/"));
  }

  /** Test exception on escape attempt
  *
  * @expectedException \UnexpectedValueException
  */
  public function testExceptionOnEscapeAttempt()
  {
    $path = Path::normalize("..", false);
  }
}
