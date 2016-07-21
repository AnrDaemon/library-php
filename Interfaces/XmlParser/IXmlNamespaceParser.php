<?php
/** XML Parser - Namespaces support interface.
*
* @package Wrappers\XmlParser
* @version SVN: $Id: IXmlNamespaceParser.php 455 2016-02-16 00:51:31Z anrdaemon $
*/

namespace AnrDaemon\Interfaces\XmlParser;

interface IXmlNamespaceParser extends IXmlBasicParser {
  /** A handler to be called when a namespace is declared.
  *
  * Namespace declarations occur inside start tags. But the namespace
  * declaration start handler is called before the start tag handler for each
  * namespace declared in that start tag.
  *
  * !NOTE: The whitespace in the public identifier will be normalized as required by the XML spec.
  *
  * @param Resource $self The XML parser resource.
  * @param String $name The namespace identifier (empty for default namespace).
  * @param String $target The schema URI for the namespace.
  * @return void
  *
  * @see \xml_set_start_namespace_decl_handler()
  */
  function ns_start($self, $name, $target);

  /** A handler to be called when leaving the scope of a namespace declaration.
  *
  * This will be called, for each namespace declaration, after the handler for
  * the end tag of the element in which the namespace was declared.
  *
  * !NOTE: The whitespace in the public identifier will be normalized as required by the XML spec.
  *
  * !NOTE: It seems, this handler is never called.
  *
  * @param Resource $self The XML parser resource.
  * @param String $name The namespace identifier (empty for default namespace).
  * @return void
  *
  * @see \xml_set_end_namespace_decl_handler()
  */
  function ns_end($self, $name);
}
