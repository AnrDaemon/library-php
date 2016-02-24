<?php
/** XML Parser - External entity support interface.
*
* @package Wrappers\XmlParser
* @version SVN: $Id: IXmlNdataHandler.php 455 2016-02-16 00:51:31Z anrdaemon $
*/

namespace AnrDaemon\Interfaces\XmlParser;

interface IXmlNdataHandler extends IXmlNamespaceParser {
  /** External notation handler
  *
  * !NOTE: The whitespace in the public identifier will be normalized as required by the XML spec.
  *
  * @param resource $self The reference to the XML parser calling the handler.
  * @param string   $name The notation's name.
  * @param void     $base The base for resolving the system identifier (system_id) of the notation declaration. Currently this parameter will always be set to an empty string.
  * @param string   $system_id System identifier of the external notation declaration.
  * @param string   $public_id Public identifier of the external notation declaration.
  * @return void
  *
  * @see \xml_set_notation_decl_handler()
  */
  function notation_handler($self, $name, $base, $system_id, $public_id);

  /** External entity handler
  *
  * !NOTE: The whitespace in the public identifier will be normalized as required by the XML spec.
  *
  * @param resource $self  The reference to the XML parser calling the handler.
  * @param string   $names The space-separated list of the names of the entities that are open for the parse of this entity (including the name of the referenced entity).
  * @param void     $base  The base for resolving the system identifier (system_id) of the external entity. Currently this parameter will always be set to an empty string.
  * @param string   $system_id The system identifier as specified in the entity declaration.
  * @param string   $public_id The public identifier as specified in the entity declaration, or an empty string if none was specified.
  * @param string   $notation  Name of the notation of this entity ( @see \xml_set_notation_decl_handler() ).
  * @return void
  *
  * @see \xml_set_unparsed_entity_decl_handler()
  */
  function ndata_handler($self, $name, $base, $system_id, $public_id, $notation);
}
