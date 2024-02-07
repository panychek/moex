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

class MarketTest extends TestCase
{
    /**
     * @var \GuzzleHttp\Handler\MockHandler
     */
    private $mock_handler = null;
    
    protected function setUp(): void
    {
        $this->mock_handler = new MockHandler();
        
        $handler = HandlerStack::create($this->mock_handler);
        Client::setExtraOption('handler', $handler);
    }
    
    protected function tearDown(): void
    {
        $this->mock_handler = null;
        
        Client::setExtraOption('handler', null);
        Client::destroyInstance();
        
        Exchange::destroy();
    }
    
    /**
     * @group Unit
     * @dataProvider marketProvider
     */
    public function testGetters(string $market_id, string $engine_id, string $expected_title)
    {
        $response_file = sprintf('%s/Response/%s_engine_markets.json', __DIR__, $engine_id);
        $body = file_get_contents($response_file);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $market = Market::getInstance($market_id, $engine_id);
        
        $this->assertEquals($market_id, $market->getId());
        $this->assertEquals($expected_title, $market->getTitle());
        $this->assertEquals($expected_title, $market->getProperty('title'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $market->getEngine());
    }
    
    /**
     * @group Unit
     */
    public function testBoards()
    {
        $response_file = sprintf('%s/Response/shares_market.json', __DIR__);
        $body = file_get_contents($response_file);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $market_id = 'shares';
        $engine_id = 'stock';
        
        $market = Market::getInstance($market_id, $engine_id);
        
        $boards = $market->getBoards();
        $this->assertIsArray($boards);
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
    }
        
    public function marketProvider()
    {
        $body = file_get_contents(__DIR__ . '/Response/engines.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $mock_handler = new MockHandler(array($response));
        $handler = HandlerStack::create($mock_handler);
        
        Client::setExtraOption('handler', $handler);
        $client = Client::getInstance();
        
        $raw_data = $client->getEngineList();
        $engines = $raw_data['engines'];
        
        $data = array();
        foreach ($engines as $engine) {
            $engine_id = $engine['name'];
            
            // markets
            $response_file = sprintf('%s/Response/%s_engine_markets.json', __DIR__, $engine_id);
            $body = file_get_contents($response_file);
            $response = new Response(200, ['Content-Type' => 'application/json'], $body);
            
            $mock_handler->append($response);
            
            $raw_data = $client->getMarketList($engine_id);
            $markets = $raw_data['markets'];
            
            foreach ($markets as $market) {
                $data[] = array($market['NAME'], $engine_id, $market['title']);
            }
        }
        
        Client::setExtraOption('handler', null);
        Client::destroyInstance();
        
        return $data;
    }
}
