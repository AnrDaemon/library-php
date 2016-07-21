<?php
/** XML Parser - External entity support interface.
*
* @package Wrappers\XmlParser
* @version SVN: $Id: IXmlEntityHandler.php 455 2016-02-16 00:51:31Z anrdaemon $
*/

namespace AnrDaemon\Interfaces\XmlParser;

interface IXmlEntityHandler extends IXmlNamespaceParser {
  /** External entity handler
  *
  * !NOTE: The whitespace in the public identifier will be normalized as required by the XML spec.
  *
  * !NOTE: If this function would return false, XML parser will stop parsing and xml_get_error_code() will return XML_ERROR_EXTERNAL_ENTITY_HANDLING.
  *
  * @param resource $self  The reference to the XML parser calling the handler.
  * @param string   $names The space-separated list of the names of the entities that are open for the parse of this entity (including the name of the referenced entity).
  * @param void     $base  The base for resolving the system identifier (system_id) of the external entity. Currently this parameter will always be set to an empty string.
  * @param string   $system_id The system identifier as specified in the entity declaration.
  * @param string   $public_id The public identifier as specified in the entity declaration, or an empty string if none was specified.
  * @return boolean true if parsing of the entity was successful. false othervise.
  *
  * @see \xml_set_external_entity_ref_handler()
  */
  function entity_handler($self, $names, $base, $system_id, $public_id);
}
