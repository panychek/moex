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
use Panychek\MoEx\Client;

class ExchangeTest extends TestCase
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
        
        Exchange::destroyInstance();
        Engine::destroyInstances();
        Market::destroyInstances();
    }
    
    /**
     * @group Unit
     */
    public function testGetters()
    {
        // engines
        $body = file_get_contents(__DIR__ . '/Response/engines.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // turnovers
        $body = file_get_contents(__DIR__ . '/Response/turnovers.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $exchange = Exchange::getInstance();
        
        $engines = $exchange->getEngines();
        $this->assertInternalType('array', $engines);
        foreach ($engines as $engine) {
            $this->assertInstanceOf(Engine::class, $engine);
        }
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertEquals(3476668.60556, $exchange->getTurnovers());
        $this->assertEquals(57711.131417900004, $exchange->getTurnovers('USD'));
        $this->assertEquals(3476668.60556, $exchange->getTurnovers('RUB'));
        $this->assertEquals(57711.131417900004, $exchange->getTurnovers('usd'));
        $this->assertEquals(3476668.60556, $exchange->getTurnovers('rub'));
        $this->assertEquals(3273085.53681, $exchange->getTurnovers('rub', '2017-07-06'));
        
        $this->assertEquals(1878607, $exchange->getNumberOfTrades());
        $this->assertEquals(1756336, $exchange->getNumberOfTrades('2017-07-06'));
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
    }
}
