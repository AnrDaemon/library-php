<?php
/** XML Parser error-to-exception wrapper.
*
* @package Wrappers\XmlParser
* @version SVN: $Id: XmlParserError.php 738 2018-03-03 19:03:36Z anrdaemon $
*/

namespace AnrDaemon\Wrappers;

final class XmlParserError extends \Exception
{
  protected $err = array();

  public function __construct($message, $parser, \SplFileObject $file = NULL)
  {
    $this->code = xml_get_error_code($parser);
    if(false === $this->code)
      throw new \BadMethodCallException('This is not a valid xml_parser resource.');

    parent::__construct($message ?: xml_error_string($this->code), $this->code);

    $this->file = $file ? $file->getPathname() : '(data stream)';
    $this->line = xml_get_current_line_number($parser);
    $this->err['srcColumn'] = xml_get_current_column_number($parser);
    $this->err['srcIndex'] = xml_get_current_byte_index($parser);
  }

  public function __get($name)
  {
    if(isset($this->err[$name]))
      return $this->err[$name];

    throw new \LogicException("Undefined property '$name'.");
  }

  public function __toString()
  {
    return "XML Parser error '{$this->message}' in {$this->file}:{$this->srcIndex}({$this->line},{$this->srcColumn})\n" .
      "Stack trace:\n" . $this->getTraceAsString();
  }
}
