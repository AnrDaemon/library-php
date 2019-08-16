<?php
/** Simplistic curl wrapper with runtime cookie persistence
*
* @version SVN: $Id: Browser.php 1023 2019-08-16 14:23:14Z anrdaemon $
*/

namespace AnrDaemon\Net;

use AnrDaemon\Exceptions\CurlException;

/** A very thin cURL wrapper
*
* The class intended to greatly simplify the use of cURL library in common cases.
*
* The most basic use of the class would be
*
*   $browser = new Browser;
*   $page = $browser->get($url);
*
* On each successful(*) call to retrieval method, the object automatically populates {@see \curl_getinfo() basic request status block}.
*
* (*)The definition of success depends on cURL handle settings.
*
* @property-read array $status Basic request status block. Equals calling {@see \curl_getinfo() \curl_getinfo()} on a handle without a second parameter. This structure is only populated after a successful(*) request.
* @property-read string $certinfo TLS certificate chain.
* @property-read string $connect_time Time in seconds it took to establish the connection.
* @property-read string $content_type Content-Type: of the requested document. NULL indicates server did not send valid Content-Type: header.
* @property-read string $download_content_length Content length of download, read from Content-Length: field.
* @property-read string $filetime Remote time of the retrieved document, with the CURLOPT_FILETIME enabled; if -1 is returned the time of the document is unknown.
* @property-read string $header_size Total size of all headers received.
* @property-read string $http_code Last received HTTP code.
* @property-read string $local_ip Local (source) IP address of the most recent connection.
* @property-read string $local_port Local (source) port of the most recent connection.
* @property-read string $namelookup_time Time in seconds until name resolving was complete.
* @property-read string $pretransfer_time Time in seconds from start until just before file transfer begins.
* @property-read string $primary_ip IP address of the most recent connection.
* @property-read string $primary_port Destination port of the most recent connection.
* @property-read string $redirect_count Number of performed redirects, with the CURLOPT_FOLLOWLOCATION option enabled.
* @property-read string $redirect_time Time in seconds of all redirection steps before final transaction was started, with the CURLOPT_FOLLOWLOCATION option enabled.
* @property-read string $redirect_url The redirection URL (the Location: header) found in the last transaction. With the CURLOPT_FOLLOWLOCATION option enabled, the last effective URL can be retrieved from `$url` property.
* @property-read string $request_header The request string sent. This is only set if the CURLINFO_HEADER_OUT is set.
* @property-read string $request_size Total size of issued requests (currently only for HTTP requests).
* @property-read string $size_download Total number of bytes downloaded.
* @property-read string $size_upload Total number of bytes uploaded.
* @property-read string $speed_download Average download speed.
* @property-read string $speed_upload Average upload speed.
* @property-read string $ssl_verify_result Result of SSL certification verification requested by setting CURLOPT_SSL_VERIFYPEER.
* @property-read string $starttransfer_time Time in seconds until the first byte is about to be transferred.
* @property-read string $total_time Total transaction time in seconds for last transfer.
* @property-read string $upload_content_length Specified size of upload.
* @property-read string $url Last effective URL.
*/
class Browser
{
  protected $curl;
  protected $info;

  /** The curl wrapper itself.
  *
  * Performs an actual call to the cURL library and judges the result.
  *
  * An exception is thrown if results are found inadequate.
  *
  * @param callable $callback The name of curl_* function to call.
  * @param mixed ...$params Arguments to the call.
  * @return mixed The results of the call.
  */
  protected function perform(callable $callback, ...$params)
  {
    $result = $callback($this->curl, ...$params);
    if(curl_errno($this->curl) !== CURLE_OK)
      throw new CurlException($this->curl);

    if($result === false)
      throw new CurlException("Unable to perform $callback - unknown error.");

    return $result;
  }

  /** cURL request wrapper method.
  *
  * Performs a request prepared from supplied `$options`
  *
  * Returns response body, if applicable.
  *
  * Upon successful(*) request, the basic status block is populated.
  *
  * (*)The definition of success depends on cURL handle settings.
  *
  * @param array $options Request-specific options (type and accompanied data).
  * @return string Response body.
  */
  protected function request(array $options)
  {
    $this->info = null;
    $this->setOpt($options);
    $result = $this->perform('curl_exec');
    $this->info = $this->perform('curl_getinfo');
    return $result;
  }

// Information and configuration

  /** Retrieving information about cURL handle
  *
  * @see \curl_getinfo()
  *
  * @param int $name A CURLINFO_* constant.
  * @return mixed The requested information.
  */
  public function getInfo($name)
  {
    return $this->perform('curl_getinfo', $name);
  }

  /** Setting cURL options.
  *
  * @see \curl_setopt()
  *
  * @param int|array $name A CURLOPT_* constant or an array of option:value pairs.
  * @param ?mixed $value The value to set the option to.
  * @return void
  */
  public function setOpt($name, $value = null)
  {
    if(is_array($name))
    {
      try
      {
        $i = 0;
        foreach($name as $opt => $value)
        {
          $this->setOpt($opt, $value);
          ++$i;
        }
      }
      catch(CurlException $e)
      {
        throw $e->getCode() ? $e : new CurlException("Set failed at #$i: " . $e->getMessage());
      }
    }
    else
    {
      try
      {
        set_error_handler(
          function($s, $m, $f, $l, $c = null)
          use($name)
          {
            throw new CurlException("$m (" . CurlOptions::name($name) . ").");
          },
          \E_WARNING
        );
        $this->perform('curl_setopt', $name, $value);
      }
      finally
      {
        restore_error_handler();
      }
    }
  }

// Method handling

  /** HTTP GET-like method caller
  *
  * Performs a body-less HTTP request on a given `$url`.
  *
  * Returns response body, if applicable.
  *
  * The request may not contain a body part.
  *
  * Upon successful(*) request, the basic status block is populated.
  *
  * (*)The definition of success depends on cURL handle settings.
  *
  * @param string $url An URL to access.
  * @param ?string $method The request method, defaults to GET.
  * @return string Response body.
  */
  public function get($url, $method = "GET")
  {
    return $this->request([
      CURLOPT_HTTPGET => true,
      CURLOPT_CUSTOMREQUEST => $method ?: "GET",
      CURLOPT_URL => "$url",
    ]);
  }

  /** HTTP POST-like method caller
  *
  * Performs HTTP request on a given `$url`.
  *
  * Returns response body, if applicable.
  *
  * The request body is generated from `$data` by cURL as defined in the
  * description of {@see \curl_setopt() CURLOPT_POSTFIELDS option}.
  *
  * Upon successful(*) request, the basic status block is populated.
  *
  * (*)The definition of success depends on cURL handle settings.
  *
  * @param string $url An URL to access.
  * @param mixed $data The POST data in CURLOPT_POSTFIELDS-appropriate format.
  * @param ?string $method The request method, defaults to POST.
  * @return string Response body.
  */
  public function post($url, $data = null, $method = "POST")
  {
    return $this->request([
      CURLOPT_POST => true,
      CURLOPT_CUSTOMREQUEST => $method ?: "POST",
      CURLOPT_URL => "$url",
      CURLOPT_POSTFIELDS => $data ?: '',
    ]);
  }

  /** HTTP PUT-like method caller
  *
  * Performs HTTP request on a given `$url`.
  *
  * Returns response body, if applicable.
  *
  * The request body is read from stream resource `$data` for `$len` bytes of content.
  *
  * Upon successful(*) request, the basic status block is populated.
  *
  * (*)The definition of success depends on cURL handle settings.
  *
  * @param string $url An URL to access.
  * @param resource $data The stream resource to read data from.
  * @param int $len Length of `$data` to read.
  * @param ?string $method The request method, defaults to PUT.
  * @return string Response body.
  */
  public function put($url, $data = null, $len = null, $method = "PUT")
  {
    return $this->request([
      CURLOPT_PUT => true,
      CURLOPT_CUSTOMREQUEST => $method ?: "PUT",
      CURLOPT_URL => "$url",
      CURLOPT_INFILE => $data,
      CURLOPT_INFILESIZE => $len,
    ]);
  }

// Magic!

  public function __construct(array $params = null)
  {
    $result = curl_init();
    if($result === false)
      throw new CurlException;

    $this->curl = $result;
    $pki = [
      CURLOPT_CAINFO => ini_get('openssl.cafile'),
      CURLOPT_CAPATH => ini_get('openssl.capath'),
    ];
    $this->setOpt((array)$params + array_filter($pki) + [
      CURLOPT_COOKIEFILE => '',
      CURLOPT_COOKIESESSION => true,
      CURLOPT_SAFE_UPLOAD => true,
      CURLOPT_RETURNTRANSFER => true,
    ]);
  }

  /** @internal */
  public function __clone()
  {
    $this->curl = curl_copy_handle($this->curl);
  }

  /** @internal */
  public function __destruct()
  {
    curl_close($this->curl);
  }

  /** @internal */
  public function __get($name)
  {
    if($name === "status")
      return $this->info;

    return $this->info[$name];
  }
}
