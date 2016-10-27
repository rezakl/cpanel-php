<?php
/**
 * Cpanel/WHM API
 *
*/

namespace Fafan\CpanelPhp;

/**
 * Provides easy to use class for calling some CPanel/WHM API functions
 *
 * @author Fafan <fafan.art@gmail.com>
 * @version v1.0.0
 * @package Fafan\CpanelPhp
 * @link https://github.com/fafan/cpanel-php
 * @since v1.0.0
*/
class Cpanel
{
  /**
  * @var string Username of your whm server. Must be string
  * @since v1.0.0
  */
  private $username;

  /**
  * @var string Password, API token or long hash of your whm server.
  * @since v1.0.0
  */
  private $password;

  /**
  * @var string Authentication type: 'token', 'hash' or 'password'. Defaults to 'hash'.
  * @since v1.0.0
  */
  private $auth_type = 'hash';

  /**
  * @var string Host of your whm server. You must set it with full host with its port and protocol.
  * @since v1.0.0
  */
  private $host;

  /**
  * @var bool Whether to verify the server's SSL certificate. Defaults to true.
  */
  private $verify_ssl = true;

  /**
  * @var int Request timeout in seconds.
  */
  private $timeout = 30;

  /**
  * @var array Sets of headers that will be sent at request.
  * @since v1.0.0
  */
  protected $headers = array();

  /**
  * Class constructor. The options must contain username, host, and password.
  * Optional: auth_type ('token', 'hash' or 'password'), verify_ssl (bool), timeout (int seconds).
  *
  * @param array $options options that will be passed and processed
  * @since v1.0.0
  */
  public function __construct($options = array())
  {
    if (empty($options))
      return;

    $this->checkOptions($options);

    if (!empty($options['auth_type']))
      $this->setAuthType($options['auth_type']);
    if (isset($options['verify_ssl']))
      $this->setVerifySsl($options['verify_ssl']);
    if (isset($options['timeout']))
      $this->setTimeout($options['timeout']);

    $this->setHost($options['host'])
      ->setAuthorization($options['username'], $options['password']);
  }

  /**
  * Magic method who will call the CPanel/WHM Api.
  * Usage: $cpanel->createacct(['username' => 'foo', 'domain' => 'foo.com']);
  *
  * @param string $function function name that will be called
  * @param array $arguments first element (optional) is an associative array of API parameters
  * @return object result of called functions
  * @since v1.0.0
  */
  public function __call($function, $arguments = array())
  {
    $params = isset($arguments[0]) ? $arguments[0] : array();
    if (!is_array($params))
      throw new \InvalidArgumentException('API parameters must be passed as an associative array', 2306);
    return $this->runQuery($function, $params);
  }

  /**
  * checking options for 'username', 'password', and 'host'. If they are not set, some exception will be thrown
  *
  * @param array $options list of options that will be checked
  * @return object return as self-object
  * @since v1.0.0
  */
  private function checkOptions($options)
  {
    if (empty($options['username']))
      throw new \Exception('Username is not set', 2301);
    if (empty($options['password']))
      throw new \Exception('Password or hash is not set', 2302);
    if (empty($options['host']))
      throw new \Exception('CPanel Host is not set', 2303);
    return $this;
  }

  /**
  * set authorization for access.
  * It only set 'username' and 'password'
  *
  * @param string $username Username of your whm server.
  * @param string $password Password, API token or long hash of your whm server.
  * @return object return as self-object
  * @since v1.0.0
  */
  public function setAuthorization($username, $password)
  {
    $this->username = $username;
    $this->password = $password;
    return $this;
  }

  /**
  * set API Host.
  *
  * @param string $host Host of your whm server.
  * @return object return as self-object
  * @since v1.0.0
  */
  public function setHost($host)
  {
    $this->host = rtrim($host, '/');
    return $this;
  }

  /**
  * set Authentication Type.
  *
  * @param string $auth_type 'token', 'hash' or 'password'
  * @return object return as self-object
  * @since v1.0.0
  */
  public function setAuthType($auth_type)
  {
    if (!in_array($auth_type, array('token', 'hash', 'password'), true))
      throw new \InvalidArgumentException("Unsupported auth_type '$auth_type'. Use 'token', 'hash' or 'password'", 2304);
    $this->auth_type = $auth_type;
    return $this;
  }

  /**
  * Enable or disable SSL certificate verification. Only disable for testing against self-signed servers.
  *
  * @param bool $verify
  * @return object return as self-object
  */
  public function setVerifySsl($verify)
  {
    $this->verify_ssl = (bool) $verify;
    return $this;
  }

  /**
  * set request timeout in seconds.
  *
  * @param int $timeout
  * @return object return as self-object
  */
  public function setTimeout($timeout)
  {
    $this->timeout = (int) $timeout;
    return $this;
  }

  /**
  * set some header.
  *
  * @param string $name key of header you want to add
  * @param string $value value of header you want to add
  * @return object return as self-object
  * @since v1.0.0
  */
  public function setHeader($name, $value = '')
  {
    $this->headers[$name] = $value;
    return $this;
  }

  /**
  * get username.
  *
  * @return string return username
  * @since v1.0.0
  */
  public function getUsername()
  {
    return $this->username;
  }

  /**
  * get authentication type.
  *
  * @return string get authentication type
  * @since v1.0.0
  */
  public function getAuthType()
  {
    return $this->auth_type;
  }

  /**
  * get password, API token or long hash.
  *
  * @return string get password or long hash
  * @since v1.0.0
  */
  public function getPassword()
  {
    return $this->password;
  }

  /**
  * get host of your whm server.
  *
  * @return string host of your whm server
  * @since v1.0.0
  */
  public function getHost()
  {
    return $this->host;
  }

  /**
  * get whether SSL certificates are verified.
  *
  * @return bool
  */
  public function getVerifySsl()
  {
    return $this->verify_ssl;
  }

  /**
  * extend http headers that will be sent.
  *
  * @return array list of headers that will be sent
  * @since v1.0.0
  */
  protected function createHeader()
  {
    $headers = $this->headers;

    $username = $this->getUsername();
    $secret = preg_replace("/[\r\n]/", '', (string) $this->getPassword());

    switch ($this->getAuthType())
    {
      case 'token':
        $headers['Authorization'] = 'whm '.$username.':'.$secret;
        break;
      case 'hash':
        $headers['Authorization'] = 'WHM '.$username.':'.$secret;
        break;
      case 'password':
        $headers['Authorization'] = 'Basic '.base64_encode($username.':'.$secret);
        break;
    }

    return $headers;
  }

  /**
  * The executor. It will run API function and get the data
  *
  * @param string $action function name that will be called.
  * @param array $params associative array of API parameters.
  * @return object results of API call
  * @throws \RuntimeException on transport, HTTP or JSON errors
  * @since v1.0.0
  */
  protected function runQuery($action, $params = array())
  {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $action))
      throw new \InvalidArgumentException("Invalid API function name '$action'", 2305);

    $url = $this->getHost().'/json-api/'.$action;
    $headers = array();
    foreach ($this->createHeader() as $header_key => $header_value)
      $headers[] = "$header_key: $header_value";

    $response = $this->sendRequest($url, $headers, http_build_query($params));

    if ($response['status'] < 200 || $response['status'] >= 300)
      throw new \RuntimeException("cPanel API '$action' returned HTTP {$response['status']}: ".substr($response['body'], 0, 500), $response['status']);

    $result = json_decode($response['body']);
    if (json_last_error() !== JSON_ERROR_NONE)
      throw new \RuntimeException("cPanel API '$action' returned invalid JSON: ".json_last_error_msg(), 2308);

    return $result;
  }

  /**
  * Send the HTTP request. Override in a subclass to mock the transport in tests.
  *
  * @param string $url
  * @param array $headers list of "Name: value" header lines
  * @param string $body url-encoded request body
  * @return array ['status' => int, 'body' => string]
  * @throws \RuntimeException when the request cannot be completed
  */
  protected function sendRequest($url, array $headers, $body)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verify_ssl ? 2 : 0);

    $result = curl_exec($ch);
    if ($result === false)
    {
      $error = curl_error($ch);
      $errno = curl_errno($ch);
      curl_close($ch);
      throw new \RuntimeException("cPanel API request failed (cURL error $errno): $error", 2307);
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array('status' => $status, 'body' => $result);
  }
}
