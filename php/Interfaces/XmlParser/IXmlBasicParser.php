<?php
/** XML Parser - Processing support interface.
*
* @package Wrappers\XmlParser
* @version SVN: $Id: IXmlBasicParser.php 455 2016-02-16 00:51:31Z anrdaemon $
*/

namespace AnrDaemon\Interfaces\XmlParser;

interface IXmlBasicParser {
  /** Start of element handler
  *
  * !NOTE: If case-folding is in effect for this parser, the element name and names of the attributes will be in uppercase letters.
  *
  * The keys of the $attrs array are the attribute names, the values are the attribute values.
  * Attribute names are case-folded on the same criteria as element names. Attribute values are not case-folded.
  * The original order of the attributes can be retrieved by walking through attribs the normal way, using each().
  * The first key in the array was the first attribute, and so on.
  *
  * @param resource $self  The reference to the XML parser calling the handler.
  * @param string   $name  The name of the element for which this handler is called.
  * @param array    $attrs The associative array with the element's attributes (if any).
  * @return void
  *
  * @see \xml_set_element_handler()
  */
  function element_start($self, $name, $attrs);

  /** End of element handler
  *
  * !NOTE: If case-folding is in effect for this parser, the element name will be in uppercase letters.
  *
  * @param resource $self  The reference to the XML parser calling the handler.
  * @param string   $name  The name of the element for which this handler is called.
  * @return void
  *
  * @see \xml_set_element_handler()
  */
  function element_end($self, $name);

  /** Character data handler
  *
  * Character data handler is called for every piece of a text in the XML document.
  * It can be called multiple times inside each fragment (for non-ASCII strings, internal entities and so on).
  *
  * @param resource $self  The reference to the XML parser calling the handler.
  * @param string   $data  Contains the character data as a string.
  * @return void
  *
  * @see \xml_set_character_data_handler()
  */
  function cdata_handler($self, $data);
}
