<?php
/** Simplistic curl wrapper with runtime cookie persistence
*
* @version SVN: $Id: Browser.php 788 2018-04-03 14:58:56Z anrdaemon $
*/

namespace AnrDaemon\Net;

use
  AnrDaemon\Exceptions\CurlException;

class Browser
{
  protected $curl;
  protected $info;

  protected function perform(callable $callback, ...$params)
  {
    $result = $callback($this->curl, ...$params);
    if(curl_errno($this->curl) !== CURLE_OK)
      throw new CurlException($this->curl);

    if($result === false)
      throw new CurlException("Unable to perform $callback - unknown error.");

    return $result;
  }

// Information and configuration

  public function getInfo($name)
  {
    return $this->perform('curl_getinfo', $name);
  }

  public function setOpt($name, $value = null)
  {
    return
      is_array($name)
      ? $this->perform('curl_setopt_array', $name)
      : $this->perform('curl_setopt', $name, $value);
  }

// Method handling

  public function get($url)
  {
    $this->info = null;
    $this->setOpt([
      CURLOPT_HTTPGET => true,
      CURLOPT_URL => "$url",
    ]);
    $result = $this->perform('curl_exec');
    $this->info = $this->perform('curl_getinfo');
    return $result;
  }

  public function post($url, $data = null)
  {
    $this->info = null;
    $this->setOpt([
      CURLOPT_POST => true,
      CURLOPT_URL => "$url",
      CURLOPT_POSTFIELDS => $data ?: '',
    ]);
    $result = $this->perform('curl_exec');
    $this->info = $this->perform('curl_getinfo');
    return $result;
  }
/* // Does not work, requires an actual file resource.
  public function put($url, \SplFileObject $data)
  {
    $this->setOpt([
      CURLOPT_PUT => true,
      CURLOPT_INFILE => $data,
    ]);
    return $this->perform('curl_exec');
  }
*/
/*
  public function customRequest($url, $data = null)
  {
    $this->setOpt([
      CURLOPT_CUSTOMREQUEST => '???',
      CURLOPT_URL => "$url",
    ]);
    return $this->perform('curl_exec');
  }
*/
// Magic!

  public function __construct(array $params = null)
  {
    $result = curl_init();
    if($result === false)
      throw new CurlException;

    $this->curl = $result;
    $this->perform('curl_setopt_array', (array)$params + [
      CURLOPT_COOKIEFILE => '',
      CURLOPT_COOKIESESSION => true,
      CURLOPT_SAFE_UPLOAD => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CAINFO => ini_get('openssl.cafile'),
      CURLOPT_CAPATH => ini_get('openssl.capath'),
    ]);
  }

  public function __get($name)
  {
    return $this->info[$name];
  }
}
