<?php
/** Curl errors wrapper
*
* @version SVN: $Id: CurlException.php 788 2018-04-03 14:58:56Z anrdaemon $
*/

namespace AnrDaemon\Exceptions;

class CurlException
extends \RuntimeException
{
  public function __construct($curl = null, \Exception $previous = null)
  {
    parent::__construct(
      is_resource($curl) ? curl_error($curl) : ($curl ?: 'Unable to initialize cURL instance - unknown error.'),
      is_resource($curl) ? curl_errno($curl) : 0,
      $previous
    );
  }
}
