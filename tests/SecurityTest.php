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
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Panychek\MoEx\Exchange;
use Panychek\MoEx\Engine;
use Panychek\MoEx\Market;
use Panychek\MoEx\Board;
use Panychek\MoEx\Security;
use Panychek\MoEx\Client;
use Panychek\MoEx\Exception\BadMethodCallException;
use Panychek\MoEx\Exception\InvalidArgumentException;

class SecurityTest extends TestCase
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
        
        Exchange::destroy();
    }
    
    /**
     * @group Unit
     */
    public function testPropertyGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // dates
        $body = file_get_contents(__DIR__ . '/Response/security_dates.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // indices
        $body = file_get_contents(__DIR__ . '/Response/security_indices.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $this->assertEquals('moex', $security->getId());
        $this->assertEquals('ПАО Московская Биржа', $security->getName());
        $this->assertEquals('ПАО Московская Биржа', $security->getProperty('name'));
        $this->assertEquals('МосБиржа', $security->getShortName());
        $this->assertEquals('МосБиржа', $security->getProperty('shortname'));
        $this->assertEquals('RU000A0JR4A1', $security->getIsin());
        $this->assertEquals('RU000A0JR4A1', $security->getProperty('isin'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $security->getEngine());
        $this->assertInstanceOf(Market::class, $security->getMarket());
        $this->assertInstanceOf(Board::class, $security->getBoard());
        
        $this->assertInternalType('array', $security->getDates());
        $this->assertEquals(2, Client::getInstance()->getCounter());
        
        $indices = $security->getIndices();
        $this->assertInternalType('array', $indices);
        
        foreach ($indices as $index) {
            $this->assertInstanceOf(Security::class, $index);
        }
        
        $this->assertEquals(3, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testLanguages()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/security_en.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#moex';
        $security = new Security($security_name);
        $this->assertEquals('ru', $security->getLanguage());
        
        $security->setLanguage('en');
        
        $this->assertEquals('en', $security->getLanguage());
        $this->assertEquals('MoscowExchange', $security->getName());
    }

    /**
     * @group Unit
     */
    public function testMarketDataGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // market data
        $body = file_get_contents(__DIR__ . '/Response/security_market_data.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $this->assertInternalType('float', $security->getLastPrice());
        $this->assertInternalType('float', $security->getOpeningPrice());
        $this->assertInternalType('float', $security->getClosingPrice());
        
        $this->assertInternalType('int', $security->getVolume());
        $this->assertInternalType('int', $security->getVolume('USD'));
        $this->assertInternalType('int', $security->getVolume('RUB'));
        $this->assertInternalType('int', $security->getVolume('usd'));
        $this->assertInternalType('int', $security->getVolume('rub'));
        
        $this->assertInternalType('float', $security->getDailyHigh());
        $this->assertInternalType('float', $security->getDailyLow());
        
        $this->assertInternalType('numeric', $security->getChange());
        $this->assertInternalType('numeric', $security->getChange('day'));
        $this->assertInternalType('numeric', $security->getChange('day', '%'));
        
        $this->assertInstanceOf(\DateTime::class, $security->getLastUpdate());
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testUnknownGetterThrowsException()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $this->expectException(BadMethodCallException::class);
        
        $security->getUnknownProperty();
    }

    /**
     * @group Unit
     */
    public function testHistoricalQuotes()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // page 1
        $body = file_get_contents(__DIR__ . '/Response/security_history_page1.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // page 2
        $body = file_get_contents(__DIR__ . '/Response/security_history_page2.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // page 3
        $body = file_get_contents(__DIR__ . '/Response/security_history_page3.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $from = '2014-01-01';
        $to = '2014-12-31';
        $data = $security->getHistoricalQuotes($from, $to);
        
        $this->assertInternalType('array', $data);
        $this->assertCount(250, $data);
        
        $this->assertArrayHasKey('2014-01-06', $data);
        $this->assertArrayHasKey('2014-12-30', $data);
        
        $day = current($data);
        $this->assertArrayHasKey('open', $day);
        $this->assertArrayHasKey('close', $day);
        $this->assertArrayHasKey('high', $day);
        $this->assertArrayHasKey('low', $day);
        $this->assertArrayHasKey('volume', $day);
        
        $this->assertEquals(4, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testInvalidDateStringThrowsException()
    {
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $this->expectException(InvalidArgumentException::class);
        
        $from = 'Invalid date';
        $data = $security->getHistoricalQuotes($from);
    }
    
    /**
     * @group Unit
     */
    public function testInvalidDateTypeThrowsException()
    {
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $this->expectException(InvalidArgumentException::class);
        
        $from = 0;
        $data = $security->getHistoricalQuotes($from);
    }
}
