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
use Panychek\MoEx\Security;
use Panychek\MoEx\SecurityGroup;
use Panychek\MoEx\Exception\InvalidArgumentException;

class ExchangeTest extends TestCase
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
     */
    public function testGetters()
    {
        // engines
        $body = file_get_contents(__DIR__ . '/Response/engines.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // security groups
        $body = file_get_contents(__DIR__ . '/Response/security_groups.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // turnovers
        $body = file_get_contents(__DIR__ . '/Response/turnovers.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $exchange = Exchange::getInstance();
        
        $engines = $exchange->getEngines();
        $this->assertIsArray($engines);
        foreach ($engines as $engine) {
            $this->assertInstanceOf(Engine::class, $engine);
        }
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $security_groups = $exchange->getSecurityGroups();
        $this->assertIsArray($security_groups);
        foreach ($security_groups as $security_group) {
            $this->assertInstanceOf(SecurityGroup::class, $security_group);
        }
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
        
        $this->assertEquals(3476668.60556, $exchange->getTurnovers());
        $this->assertEquals(57711.131417900004, $exchange->getTurnovers('USD'));
        $this->assertEquals(3476668.60556, $exchange->getTurnovers('RUB'));
        $this->assertEquals(57711.131417900004, $exchange->getTurnovers('usd'));
        $this->assertEquals(3476668.60556, $exchange->getTurnovers('rub'));
        $this->assertEquals(3273085.53681, $exchange->getTurnovers('rub', '2017-07-06'));
        
        $this->assertEquals(1878607, $exchange->getNumberOfTrades());
        $this->assertEquals(1756336, $exchange->getNumberOfTrades('2017-07-06'));
        
        $this->assertEquals(3, Client::getInstance()->getCounter());
    }
    
    /**
     * @group Unit
     */
    public function testLanguages()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security_en.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $this->assertEquals('ru', Exchange::getLanguage());
        
        Exchange::setLanguage('en');        
        $this->assertEquals('en', Exchange::getLanguage());
        
        $security_name = '#moex';
        $security = new Security($security_name);
        $this->assertEquals('MoscowExchange', $security->getName());
    }
    
    /**
     * @group Unit
     */
    public function testSearch()
    {
        $body = file_get_contents(__DIR__ . '/Response/security_search.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $search_string = 'moex';
        $securities = Exchange::findSecurities($search_string);
        
        foreach ($securities as $security) {
            $this->assertInstanceOf(Security::class, $security);
        }
        
        $this->assertEquals(6, count($securities));
    }
    
    /**
     * @group Unit
     */
    public function testRubleRates()
    {
        $body = file_get_contents(__DIR__ . '/Response/usd_rub_tod.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $body = file_get_contents(__DIR__ . '/Response/usd_rub_tod.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $body = file_get_contents(__DIR__ . '/Response/eur_rub_tod.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $this->assertEquals(62.71, Exchange::getRubleRate());
        $this->assertEquals(62.71, Exchange::getRubleRate('usd'));
        $this->assertEquals(73.24, Exchange::getRubleRate('eur'));
        
        $this->assertEquals(3, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testUnsupportedCurrencyThrowsException()
    {
        $exchange = Exchange::getInstance();
        
        $this->expectException(InvalidArgumentException::class);
        
        $data = $exchange->getTurnovers('EUR');
    }
}
