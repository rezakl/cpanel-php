<?php
use Rezakl\CpanelPhp\Cpanel;
use PHPUnit\Framework\TestCase;

/**
 * Captures requests instead of sending them over the network.
 */
class FakeCpanel extends Cpanel
{
  public $requests = array();
  public $response = array('status' => 200, 'body' => '{"ok":1}');

  protected function sendRequest($url, array $headers, $body)
  {
    $this->requests[] = compact('url', 'headers', 'body');
    return $this->response;
  }
}

class CpanelTest extends TestCase
{
  private function options($overrides = array())
  {
    return array_merge(array(
      'host' => 'https://whm.example.com:2087',
      'username' => 'root',
      'password' => 'secret',
    ), $overrides);
  }

  public function testConfiguration()
  {
    $options = $this->options(array('auth_type' => 'password'));
    $cpanel = new Cpanel($options);

    $this->assertEquals($options['host'], $cpanel->getHost());
    $this->assertEquals($options['username'], $cpanel->getUsername());
    $this->assertEquals($options['password'], $cpanel->getPassword());
    $this->assertEquals('password', $cpanel->getAuthType());
    $this->assertTrue($cpanel->getVerifySsl());
  }

  public function testAuthTypeDefaultsToHash()
  {
    $cpanel = new FakeCpanel($this->options());
    $cpanel->listaccts();

    $this->assertEquals('hash', $cpanel->getAuthType());
    $this->assertContains('Authorization: WHM root:secret', $cpanel->requests[0]['headers']);
  }

  public function testTokenAuthHeader()
  {
    $cpanel = new FakeCpanel($this->options(array('auth_type' => 'token')));
    $cpanel->listaccts();

    $this->assertContains('Authorization: whm root:secret', $cpanel->requests[0]['headers']);
  }

  public function testPasswordAuthIsBase64Encoded()
  {
    $cpanel = new FakeCpanel($this->options(array('auth_type' => 'password')));
    $cpanel->listaccts();

    $this->assertContains('Authorization: Basic '.base64_encode('root:secret'), $cpanel->requests[0]['headers']);
  }

  public function testInvalidAuthTypeIsRejected()
  {
    $this->expectException(\InvalidArgumentException::class);
    new Cpanel($this->options(array('auth_type' => 'bogus')));
  }

  public function testArgumentsAreSentAsPostBody()
  {
    $cpanel = new FakeCpanel($this->options());
    $cpanel->createacct(array('username' => 'foo', 'domain' => 'foo.com'));

    $request = $cpanel->requests[0];
    $this->assertEquals('https://whm.example.com:2087/json-api/createacct', $request['url']);
    $this->assertEquals('username=foo&domain=foo.com', $request['body']);
  }

  public function testResponseIsDecoded()
  {
    $cpanel = new FakeCpanel($this->options());
    $result = $cpanel->listaccts();

    $this->assertEquals(1, $result->ok);
  }

  public function testHttpErrorThrows()
  {
    $cpanel = new FakeCpanel($this->options());
    $cpanel->response = array('status' => 403, 'body' => 'Access denied');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionCode(403);
    $cpanel->listaccts();
  }

  public function testInvalidJsonThrows()
  {
    $cpanel = new FakeCpanel($this->options());
    $cpanel->response = array('status' => 200, 'body' => '<html>login</html>');

    $this->expectException(\RuntimeException::class);
    $cpanel->listaccts();
  }

  public function testInvalidFunctionNameIsRejected()
  {
    $cpanel = new FakeCpanel($this->options());

    $this->expectException(\InvalidArgumentException::class);
    $cpanel->{'../cpsess/evil'}();
  }

  public function testMissingOptionsThrow()
  {
    $this->expectExceptionCode(2303);
    new Cpanel(array('username' => 'root', 'password' => 'x'));
  }
}
