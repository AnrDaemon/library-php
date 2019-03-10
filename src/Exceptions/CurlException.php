<?php
/** Curl errors wrapper
*
* @version SVN: $Id: CurlException.php 993 2019-03-04 18:12:58Z anrdaemon $
*/

namespace AnrDaemon\Exceptions;

class CurlException
extends \RuntimeException
{
  public function __construct($curl = null, \Exception $previous = null)
  {
    if(is_resource($curl))
    {
      $error = curl_errno($curl);
      $message = version_compare(PHP_VERSION, '5.5.0', '<') ? "cURL error #{$error}" : curl_strerror($error);
      $text = curl_error($curl);
      if(!empty($text))
      {
        $message .= ": {$text}";
      }
    }
    else
    {
      $message = $curl ?: 'Unable to initialize cURL instance: unknown error';
      $error = 0;
    }

    parent::__construct($message, $error, $previous);
  }
}
