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
use Panychek\MoEx\Client;
use Panychek\MoEx\Exception\DataException;

class EngineTest extends TestCase
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
     * @dataProvider engineProvider
     */
    public function testGetters(string $engine_id, string $expected_title)
    {
        // engine
        $response_file = sprintf('%s/Response/%s_engine.json', __DIR__, $engine_id);
        $body = file_get_contents($response_file);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // markets
        $response_file = sprintf('%s/Response/%s_engine_markets.json', __DIR__, $engine_id);
        $body = file_get_contents($response_file);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // turnovers
        $body = file_get_contents(__DIR__ . '/Response/turnovers.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $engine = Engine::getInstance($engine_id);
        $engine->setCurrentDateTime('2017-07-07');
        
        $this->assertEquals($engine_id, $engine->getId());
        $this->assertEquals($expected_title, $engine->getTitle());
        $this->assertEquals($expected_title, $engine->getProperty('title'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $markets = $engine->getMarkets();
        $this->assertIsArray($markets);
        foreach ($markets as $market) {
            $this->assertInstanceOf(Market::class, $market);
        }
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
        
        $turnovers = $engine->getTurnovers();
        $this->assertTrue(in_array(gettype($turnovers), array('NULL', 'double')));
        
        // if there are no turnovers, there are no number of trades
        if (!empty($turnovers)) {
            $num_trades = $engine->getNumberOfTrades();
            $this->assertTrue(in_array(gettype($num_trades), array('NULL', 'integer')));
        }
        
        $this->assertEquals(3, Client::getInstance()->getCounter());
        
        // capitalization
        if (method_exists($engine, 'getCapitalization')) {
            $response_file = sprintf('%s/Response/%s_engine_capitalization.json', __DIR__, $engine_id);
            $body = file_get_contents($response_file);
            $capitalization_response = new Response(200, ['Content-Type' => 'application/json'], $body);
            
            $this->mock_handler->append($capitalization_response);
            
            $this->assertIsFloat($engine->getCapitalization());
            
            $this->assertEquals(4, Client::getInstance()->getCounter());
            
            
            // unexpected response
            $body = '{}';
            $capitalization_response = new Response(200, ['Content-Type' => 'application/json'], $body);
            
            $this->mock_handler->append($capitalization_response);
            
            $this->expectException(DataException::class);
            $this->expectExceptionCode(DataException::EMPTY_RESULT);
            
            $engine->getCapitalization();
        }
    }
    
    public function engineProvider()
    {
        $body = file_get_contents(__DIR__ . '/Response/engines.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $mock_handler = new MockHandler(array($response));
        $handler = HandlerStack::create($mock_handler);
        
        Client::setExtraOption('handler', $handler);
        $client = Client::getInstance();
        
        $raw_data = $client->getEngineList();
        
        $data = array();
        foreach ($raw_data['engines'] as $v) {
            $data[] = array($v['name'], $v['title']);
        }
        
        Client::setExtraOption('handler', null);
        Client::destroyInstance();
        
        return $data;
    }
}
