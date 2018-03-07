<?php
/** XML Parser wrapper
*
* @package Wrappers\XmlParser
* @version SVN: $Id: XmlParser.php 738 2018-03-03 19:03:36Z anrdaemon $
*/

namespace AnrDaemon\Wrappers;

use AnrDaemon\Interfaces\XmlParser as Features;

abstract class XmlParser
{
  protected $parser = NULL;
  protected $file = NULL;
  protected $options = array();

  final protected function init()
  {
    if($this instanceof Features\IXmlNamespaceParser)
    {
      $this->parser = xml_parser_create_ns('UTF-8');
      // Set up start namespace declaration handler
      xml_set_start_namespace_decl_handler($this->parser, 'ns_start');
      // Set up end namespace declaration handler
      xml_set_end_namespace_decl_handler($this->parser, 'ns_end');
    }
    elseif($this instanceof Features\IXmlBasicParser)
    {
      $this->parser = xml_parser_create('UTF-8');
    }
    else
      throw new \BadMethodCallException('This class does not implements the XML Parser capabilities. Please implement either IXmlBasicParser or IXmlNamespaceParser.');

    xml_set_object($this->parser, $this);
    foreach($this->options as $option => $value)
      xml_parser_set_option($this->parser, $option, $value);

    if($this instanceof Features\IXmlProcessorParser)
      // Set up processing instruction (PI) handler
      xml_set_processing_instruction_handler($this->parser, 'pi_handler');
    if($this instanceof Features\IXmlEntityHandler)
      // Set up external entity reference handler
      xml_set_external_entity_ref_handler($this->parser, 'entity_handler');
    if($this instanceof Features\IXmlNdataHandler)
    {
      // Set up notation declaration handler
      xml_set_notation_decl_handler($this->parser, 'notation_handler');
      // Set up unparsed entity declaration handler
      xml_set_unparsed_entity_decl_handler($this->parser, 'ndata_handler');
    }

    xml_set_element_handler($this->parser, "element_start", "element_end");
    xml_set_character_data_handler($this->parser, "cdata_handler");

    if($this instanceof Features\IXmlDefaultHandler)
    {
      if(!defined('ACTIVATE_XML_PARSER_DEFAULT_HANDLER_I_KNOW_WHAT_AM_I_DOING'))
      {
        trigger_error('Active default handler interferes with many XML features like internal parsable entities.',
          E_USER_WARNING);
      }
      // Set up default (fallback) handler.
      // Warning: Interferes with INTERNAL ENTITY declarations like
      // <!ENTITY a 'b'>
      xml_set_default_handler($this->parser, "default_handler");
    }
  }

  final protected function destroy()
  {
    if(xml_parser_free($this->parser))
    {
      $this->parser = NULL;
    }
    else
    {
      throw new \RuntimeException('I know not what you did, but the parser I created may not be released.');
    }
    $this->file = null;
  }

  final function setOption($name, $value)
  {
    switch($name)
    {
      case XML_OPTION_CASE_FOLDING: // (integer) Controls whether case-folding is enabled for this XML parser. Enabled by default.
      case XML_OPTION_SKIP_TAGSTART:// (integer) Specify how many characters should be skipped in the beginning of a tag name.
      case XML_OPTION_SKIP_WHITE:   // (integer) Whether to skip values consisting of whitespace characters.
        $this->options[$name] = $value;
        break;
      case XML_OPTION_TARGET_ENCODING:
        throw new \InvalidArgumentException('Only UTF-8 encoding is supported. If your XML is not in UTF-8, use stream context to transform the encoding.');
      default:
        throw new \InvalidArgumentException('Option is unknown or unsupported.');
    }
  }

  final function getOption($name)
  {
    switch($name)
    {
      case XML_OPTION_CASE_FOLDING: // (integer) Controls whether case-folding is enabled for this XML parser. Enabled by default.
      case XML_OPTION_SKIP_TAGSTART:// (integer) Specify how many characters should be skipped in the beginning of a tag name.
      case XML_OPTION_SKIP_WHITE:   // (integer) Whether to skip values consisting of whitespace characters.
        return $this->options[$name];
      case XML_OPTION_TARGET_ENCODING:
        return 'UTF-8';
      default:
        throw new \InvalidArgumentException('Option is unknown or unsupported.');
    }
  }

  final function parseString($data)
  {
    $this->init();
    $this->parse($data, true);
    $this->destroy();
    return true;
  }

  final function parseFile(\SplFileInfo $file)
  {
    $this->file = $file->openFile('rb');
    if(version_compare(PHP_VERSION, '5.5.11', '<'))
      $this->file->setMaxLineLen(4096);
    $this->init();
    while($data = version_compare(PHP_VERSION, '5.5.11', '<') ? $this->file->fgets() : $this->file->fread(4096))
      $this->parse($data, false);
    $this->parse(NULL, true);
    $this->destroy();
    return true;
  }

  final protected function parse($data, $final = false)
  {
    if(xml_parse($this->parser, $data, $final))
      return true;
    throw new XmlParserError(NULL, $this->parser, $this->file);
  }

  final function __call($name, $arguments)
  {
    // JSON_OPTIONS(1856) = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION;
    // Bare numeric value for compatibility with versions that do not have certain capabilities.
    print __CLASS__ . "::$name " . json_encode($arguments, 1856) . "\n";
    throw new \BadMethodCallException(__CLASS__ . "::$name is not implemented. Care to lend a hand?");
  }
}
