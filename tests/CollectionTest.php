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
use Panychek\MoEx\Security;
use Panychek\MoEx\SecurityGroup;
use Panychek\MoEx\Collection;
use Panychek\MoEx\Client;

class CollectionTest extends TestCase
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
    public function testGetters()
    {
        // collection
        $body = file_get_contents(__DIR__ . '/Response/collection.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // securities, page 1
        $body = file_get_contents(__DIR__ . '/Response/collection_securities_page1.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // securities, page 2
        $body = file_get_contents(__DIR__ . '/Response/collection_securities_page2.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // securities, page 3
        $body = file_get_contents(__DIR__ . '/Response/collection_securities_page3.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // securities, page 4
        $body = file_get_contents(__DIR__ . '/Response/collection_securities_page4.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        
        $security_group_id = 'stock_shares';
        $collection_id = 'stock_shares_one';
        
        $collection = Collection::getInstance($collection_id, $security_group_id);
        
        $this->assertEquals($collection_id, $collection->getId());
        $this->assertEquals('Уровень 1', $collection->getTitle());
        $this->assertEquals('Уровень 1', $collection->getProperty('title'));
        
        $this->assertInstanceOf(SecurityGroup::class, $collection->getSecurityGroup());
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $securities = $collection->getSecurities();
        $this->assertInternalType('array', $securities);
        foreach ($securities as $security) {
            $this->assertInstanceOf(Security::class, $security);
        }
    }
}
