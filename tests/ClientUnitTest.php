<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx\Tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Panychek\MoEx\Client;
use Panychek\MoEx\Exception\AuthenticationException;
use Panychek\MoEx\Exception\DataException;
use Panychek\MoEx\Exception\InvalidArgumentException;

class ClientUnitTest extends TestCase
{
    /**
     * @var \GuzzleHttp\Handler\MockHandler
     */
    private $mock_handler = null;
    
    protected function setUp() {
        $this->mock_handler = new MockHandler();
        
        $handler = HandlerStack::create($this->mock_handler);
        Client::setExtraOption('handler', $handler);
    }
    
    protected function tearDown()
    {
        $this->mock_handler = null;
        
        Client::setExtraOption('handler', null);
        Client::destroyInstance();
    }
    
    /**
     * @group Unit
     */
    public function testFailedRequestThrowsException()
    {
        $mock = $this->getMockBuilder(Client::class)->setMethods(['doRequest'])->getMock();
        $mock->method('doRequest')->will($this->throwException(new TransferException));
        
        $this->expectException(DataException::class);
        $this->expectExceptionCode(DataException::FAILED_REQUEST);
        
        $reflection = new \ReflectionClass($mock);
        $method = $reflection->getMethod('request');
        $method->setAccessible(true);
        
        $response = $method->invokeArgs($mock, array('invalid_uri'));
    }
    
    /**
     * @group Unit
     */
    public function testInvalidJsonResponseThrowsException()
    {
        $mock = $this->getMockBuilder(Client::class)->setMethods(array('request'))->getMock();
        $mock->method('request')->willReturn('Invalid JSON string');
        
        $this->expectException(DataException::class);
        $this->expectExceptionCode(DataException::INVALID_RESPONSE);
        
        $reflection = new \ReflectionClass(Client::class);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        
        $response = $method->invokeArgs($mock, array('uri'));
    }
    
    /**
     * @group Unit
     * @dataProvider responseProvider
     */
    public function testUnsupportedResponseFormatThrowsException($response, $expected_status)
    {
        $mock = $this->getMockBuilder(Client::class)->setMethods(array('request'))->getMock();
        
        $response = json_encode($response);
        $mock->method('request')->willReturn($response);
        
        if (!$expected_status) {
            $this->expectException(DataException::class);
            $this->expectExceptionCode(DataException::INVALID_RESPONSE);
        }
        
        $reflection = new \ReflectionClass(Client::class);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        
        $response = $method->invokeArgs($mock, array('uri'));
        
        $this->assertInternalType('array', $response);
    }
    
    public function responseProvider()
    {
        return array(
            array(
                array(),
                true
            ),
            array(
                array(
                    'section' => array()
                ),
                false
            ),
            array(
                array(
                    'section' => array(
                        'columns' => array()
                    )
                ),
                false
            ),
            array(
                array(
                    'section' => array(
                        'data' => array()
                    )
                ),
                false
            ),
            array(
                array(
                    'section' => array(
                        'columns' => array(),
                        'data' => array()
                    )
                ),
                true
            )
        );
    }
    
    /**
     * @group Unit
     */
    public function testSuccessfulAuthentication()
    {
        // basic authentication
        $headers = array(
            'Set-Cookie' => 'MicexPassportCert=value; path=/; expires=Tue, 01-Jan-30 00:00:00 GMT; domain=.moex.com'
        );
        
        $response = new Response(200, $headers);
        $this->mock_handler->append($response);
        
        // engines
        $body = file_get_contents(__DIR__ . '/Response/engines.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $client = Client::getInstance();
        
        $username = 'username';
        $password = 'password';
        $status = $client->authenticate($username, $password);
        $this->assertTrue($status);
        
        // ensuring that the cookie is being sent
        $client->setRequestOption('on_stats', function (TransferStats $stats) {
            $headers = $stats->getRequest()->getHeaders();
            
            $cookie = \GuzzleHttp\Cookie\SetCookie::fromString($headers['Cookie'][0]);
            
            $this->assertEquals(Client::AUTH_CERT_COOKIE, $cookie->getName());
            $this->assertEquals('value', $cookie->getValue());
        });
        
        $this->assertInternalType('array', $client->getEngineList());
    }
    
    /**
     * @group Unit
     */
    public function testFailedAuthenticationThrowsException()
    {
        $response = new Response(401);
        $this->mock_handler->append($response);
        
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);
        
        $username = 'username';
        $password = 'password';
        Client::getInstance()->authenticate($username, $password);
        
        Client::setExtraOption('handler', null);
        Client::destroyInstance();
    }
    
    /**
     * @group Unit
     */
    public function testMissingAuthenticationCertificateThrowsException()
    {
        $headers = array();
        $response = new Response(200, $headers);
        $this->mock_handler->append($response);
        
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(0);
        
        $username = 'username';
        $password = 'password';
        Client::getInstance()->authenticate($username, $password);
        
        Client::setExtraOption('handler', null);
        Client::destroyInstance();
    }
    
    /**
     * @group Unit
     */
    public function testLanguages()
    {
        $client = Client::getInstance();
        
        $this->assertEquals('ru', $client->getLanguage());
        
        $client->setLanguage('en');
        $this->assertEquals('en', $client->getLanguage());
    }
    
    /**
     * @group Unit
     */
    public function testUnsupportedLanguageThrowsException()
    {
        $client = Client::getInstance();
        
        $this->expectException(InvalidArgumentException::class);
        $client->setLanguage('invalid_language');
    }
    
}
