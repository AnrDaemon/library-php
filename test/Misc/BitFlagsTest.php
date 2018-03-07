<?php

namespace AnrDaemon\Tests\Misc;

use
  AnrDaemon\Misc\BitFlags,
  PHPUnit\Framework\TestCase;

final class BitFlagsTest
extends TestCase
{
  protected static $bf;

  public static function setUpBeforeClass()
  {
    static::$bf = new BitFlags(substr('0123456789;@ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 0, PHP_INT_SIZE * 8));
  }

  /** Provide number of masks for test
  */
  public function bitmasksProvider()
  {
    $list = [
      'some small number' => [42, '135'],
      'some medium number' => [25004, '23578BC'],
      'some big 32-bit number' => [780025004, '2357;@ABFGHIJKNOPR'],
    ];

    return $list;
  }

  /** Test some simple numbers conversion.
  * @dataProvider bitmasksProvider
  */
  public function testCanConvertFromRegularMask($mask, $flags)
  {
    $this->assertTrue($flags === $this::$bf->toFlags($mask));
  }

  /** Test negative numbers conversion.
  */
  public function testCanConvertFrom32bitNegative()
  {
    if(PHP_INT_SIZE !== 4)
      return $this->markTestSkipped('32-bit integer support required.');

    $i = unpack('l', "\x2B\x59\xDC\x99");
    $mask = reset($i);
    $flags = '01358@ACGHIKLMPQT';

    $this->assertTrue($flags === $this::$bf->toFlags($mask));
  }

  /** Test negative numbers conversion.
  */
  public function testCanConvertFrom64bitNegative()
  {
    if(PHP_INT_SIZE !== 8)
      return $this->markTestSkipped('64-bit integer support required.');

    $i = unpack('q', "\x2B\x59\xDC\x00\x00\x00\x00\x80");
    $mask = reset($i);
    $flags = '01358@ACGHIKLz';

    $this->assertTrue($flags === $this::$bf->toFlags($mask));
  }

  /** Test flags decoding.
  * @dataProvider bitmasksProvider
  */
  public function testCanConvertFromRegularFlags($mask, $flags)
  {
    $this->assertTrue($mask === $this::$bf->toMask($flags));
  }

  /** Test duplicated flags decoding.
  */
  public function testDecodingOfRepeatedFlags()
  {
    $this->assertTrue(4096 === $this::$bf->toMask('AA'));
  }

  /** Test empty dictionary.
  * @expectedException \LengthException
  */
  public function testDeclineEmptyDictionary()
  {
    new BitFlags('');
  }

  /** Test truncated dictionary.
  * @expectedException \PHPUnit_Framework_Error_Notice
  */
  public function testNotifyTruncatedDictionary()
  {
    // PHP_INT_SIZE is evenly divisible by 4(bytes).
    // We multiply it by 8+1 bits to create value definitely bigger than its
    // bit length, then pad the string of four characters to that length.
    new BitFlags(str_pad('', PHP_INT_SIZE * 9, '0123'));
  }
}
