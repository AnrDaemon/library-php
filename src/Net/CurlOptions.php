<?php

namespace AnrDaemon\Net;

if(!\extension_loaded("curl"))
{
  \trigger_error("'curl' extension required.", \E_USER_ERROR);
}

use AnrDaemon\Exceptions\CurlException;

class CurlOptions
{
  protected static $map;

  public static function id($name)
  {
    if(is_numeric($name))
      return (int)$name;

    return constant($name);
  }

  public static function name($id)
  {
    if(!isset(static::$map[$id]))
      throw new CurlException("Unknown option id '$id'.");

    return static::$map[$id];
  }

// Magic!

  public function __construct()
  {
    if(!static::$map)
    {
      $arr = get_defined_constants(true)["curl"];

      /** Cleanup constants list from duplicated names
      *
      * Array is "dupe => real".
      *
      * If "real" is not defined, "dupe" will be left untouched.
      */
      foreach([
        'CURLOPT_ENCODING' => 'CURLOPT_ACCEPT_ENCODING',
        'CURLOPT_FTPAPPEND' => 'CURLOPT_APPEND',
        'CURLOPT_FTPLISTONLY' => 'CURLOPT_DIRLISTONLY',
        'CURLOPT_FTP_SSL' => 'CURLOPT_USE_SSL',
        'CURLOPT_READDATA' => 'CURLOPT_INFILE',
        'CURLOPT_SSLKEYPASSWD' => 'CURLOPT_KEYPASSWD',
        'CURLOPT_SSLCERTPASSWD' => 'CURLOPT_KEYPASSWD',
        'CURLOPT_KRBLEVEL' => 'CURLOPT_KRB4LEVEL',
      ] as $dupe => $real)
      {
        if(isset($arr[$real])) unset($arr[$dupe]);
      }

      foreach($arr as $key => $value)
      {
        if($key === "CURLINFO_HEADER_OUT" || substr($key, 0, 8) === "CURLOPT_")
        {
          static::$map[$value] = $key;
        }
        else
        {
          unset($arr[$key]);
        }
      }

      if(count(static::$map) !== count($arr))
      {
        $dupes = array_diff($arr, array_flip(static::$map));
        trigger_error("Duplicated cURL options defined: " . join(", ", array_keys($dupes)), E_USER_WARNING);
      }
    }
  }
}

return new CurlOptions;
