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
use Panychek\MoEx\Board;
use Panychek\MoEx\Client;

class BoardTest extends TestCase
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
        $market_id = 'stock';
        $engine_id = 'shares';
        $board_id = 'TQBR';
        
        // board
        $body = file_get_contents(__DIR__ . '/Response/board.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $board = new Board($board_id, $market_id, $engine_id);
        
        $this->assertEquals($board_id, $board->getId());
        $this->assertEquals('Т+ Акции и ДР', $board->getTitle());
        $this->assertEquals('Т+ Акции и ДР', $board->getProperty('title'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $board->getEngine());
        $this->assertInstanceOf(Market::class, $board->getMarket());
    }
}
