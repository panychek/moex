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
use Panychek\MoEx\SecurityGroup;
use Panychek\MoEx\Collection;
use Panychek\MoEx\Client;

class SecurityGroupTest extends TestCase
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
        
        // security groups
        $body = file_get_contents(__DIR__ . '/Response/security_groups.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // collections
        $body = file_get_contents(__DIR__ . '/Response/collections.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        
        $security_group_id = 'stock_shares';
        $security_group = SecurityGroup::getInstance($security_group_id);
        
        $this->assertEquals($security_group_id, $security_group->getId());
        $this->assertEquals('Акции', $security_group->getTitle());
        $this->assertEquals('Акции', $security_group->getProperty('title'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $collections = $security_group->getCollections();
        $this->assertInternalType('array', $collections);
        foreach ($collections as $collection) {
            $this->assertInstanceOf(Collection::class, $collection);
        }
        
        $collection = $security_group->getCollection('one');
        $this->assertInstanceOf(Collection::class, $collection);
    }
}
