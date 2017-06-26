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
use Panychek\MoEx\Engine;
use Panychek\MoEx\Market;
use Panychek\MoEx\Client;

class MarketTest extends TestCase
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
    public function testGetters()
    {
        $market_id = 'shares';
        $engine_id = 'stock';
        
        $response_file = sprintf('%s/Response/%s_engine_markets.json', __DIR__, $engine_id);
        $body = file_get_contents($response_file);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $market = new Market($market_id, $engine_id);
        
        $this->assertEquals($market_id, $market->getId());
        $this->assertEquals('Рынок акций', $market->getTitle());
        $this->assertEquals('Рынок акций', $market->getProperty('title'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $market->getEngine());
    }
}
