<?php
/** XML Parser - Processing instructions support interface.
*
* @package Wrappers\XmlParser
* @version SVN: $Id: IXmlProcessorParser.php 455 2016-02-16 00:51:31Z anrdaemon $
*/

namespace AnrDaemon\Interfaces\XmlParser;

interface IXmlProcessorParser extends IXmlBasicParser {
  /** Processing instructions (XML PI) handler
  *
  * @param resource $self The reference to the XML parser calling the handler.
  * @param string   $name The processor name.
  * @param string   $data The processing instructions.
  * @return void
  *
  * @see \xml_set_processing_instruction_handler()
  */
  function pi_handler($self, $name, $data);
}
