<?php
/** XML Parser - Default handler support
*
* @package Wrappers\XmlParser
* @version SVN: $Id: IXmlDefaultHandler.php 738 2018-03-03 19:03:36Z anrdaemon $
*/

namespace AnrDaemon\Interfaces\XmlParser;

/** Declares your class to support 'default' handler.
*
* Implementing this handler is STRONGLY NOT RECOMMENDED, as it
* interferes with INTERNAL ENTITY declarations like
*
*   <!ENTITY a 'b'>
*/
interface IXmlDefaultHandler extends IXmlBasicParser
{
  /** Sets the default handler function for the XML parser.
  *
  * !NOTE: Implementing this handler is STRONGLY NOT RECOMMENDED, as it interferes with INTERNAL ENTITY declarations like
  *
  *   <!ENTITY a 'b'>
  *
  * @param resource $self The reference to the XML parser calling the handler.
  * @param string   $data The character data. This may be the XML declaration, document type declaration, entities or other data for which no other handler exists.
  * @return boolean true if parsing was successful. false othervise.
  *
  * @see \xml_set_default_handler()
  */
  function default_handler($self, $data);
}
