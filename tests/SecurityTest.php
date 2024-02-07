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
use Panychek\MoEx\Issuer;
use Panychek\MoEx\Client;
use Panychek\MoEx\Exception\BadMethodCallException;
use Panychek\MoEx\Exception\InvalidArgumentException;

class SecurityTest extends TestCase
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
    public function testCurrencyPairPropertyGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/selt_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#USD000UTSTOM';
        $security = new Security($security_name);
        
        $this->assertEquals('USD000UTSTOM', $security->getId());
        $this->assertEquals('USDRUB_TOM - USD/РУБ', $security->getName());
        $this->assertEquals('USDRUB_TOM - USD/РУБ', $security->getProperty('name'));
        $this->assertEquals('USDRUB_TOM', $security->getShortName());
        $this->assertEquals('USDRUB_TOM', $security->getProperty('shortname'));
        $this->assertEquals('1000', $security->getLotSize());
        $this->assertEquals('1000', $security->getProperty('lotsize'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $security->getEngine());
        $this->assertInstanceOf(Market::class, $security->getMarket());
        $this->assertInstanceOf(Board::class, $security->getBoard());
    }

    /**
     * @group Unit
     */
    public function testFuturesContractPropertyGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/forts_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#SiZ7';
        $security = new Security($security_name);
        
        $this->assertEquals('SiZ7', $security->getId());
        $this->assertEquals('Фьючерсный контракт на курс безналичного доллара Si-12.17', $security->getName());
        $this->assertEquals('Фьючерсный контракт на курс безналичного доллара Si-12.17', $security->getProperty('name'));
        $this->assertEquals('Si-12.17', $security->getShortName());
        $this->assertEquals('Si-12.17', $security->getProperty('shortname'));
        $this->assertEquals('Si', $security->getAssetCode());
        $this->assertEquals('Si', $security->getProperty('assetcode'));
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $security->getEngine());
        $this->assertInstanceOf(Market::class, $security->getMarket());
        $this->assertInstanceOf(Board::class, $security->getBoard());
    }

    /**
     * @group Unit
     */
    public function testBondPropertyGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/bonds_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#RU000A0JVBS1';
        $security = new Security($security_name);
        
        $this->assertEquals('RU000A0JVBS1', $security->getId());
        $this->assertEquals('БИНБАНК ПАО БО-14', $security->getName());
        $this->assertEquals('БИНБАНК ПАО БО-14', $security->getProperty('name'));
        $this->assertEquals('БинбанкБ14', $security->getShortName());
        $this->assertEquals('БинбанкБ14', $security->getProperty('shortname'));
        $this->assertEquals('RU000A0JVBS1', $security->getIsin());
        $this->assertEquals('RU000A0JVBS1', $security->getProperty('isin'));
        $this->assertEquals('1000', $security->getFaceValue());
        $this->assertEquals('11.75', $security->getCouponRate());
        $this->assertEquals('58.59', $security->getCouponValue());
        $this->assertEquals('2017-11-29', $security->getCouponDate());
        $this->assertEquals('2021-05-26', $security->getMaturityDate());
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $security->getEngine());
        $this->assertInstanceOf(Market::class, $security->getMarket());
        $this->assertInstanceOf(Board::class, $security->getBoard());
    }

    /**
     * @group Unit
     */
    public function testIndexPropertyGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/index_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#RTSI';
        $security = new Security($security_name);
        
        $this->assertEquals('RTSI', $security->getId());
        $this->assertEquals('Индекс РТС', $security->getName());
        $this->assertEquals('Индекс РТС', $security->getProperty('name'));
        $this->assertEquals('Индекс РТС', $security->getShortName());
        $this->assertEquals('Индекс РТС', $security->getProperty('shortname'));
        $this->assertEquals('100', $security->getInitialValue());
        $this->assertEquals('100', $security->getProperty('initialvalue'));
        $this->assertEquals('12666080264', $security->getInitialCapitalization());
        $this->assertEquals('1995-09-01', $security->getIssueDate());
        
        $this->assertEquals(1, Client::getInstance()->getCounter());
        
        $this->assertInstanceOf(Engine::class, $security->getEngine());
        $this->assertInstanceOf(Market::class, $security->getMarket());
        $this->assertInstanceOf(Board::class, $security->getBoard());
    }

    /**
     * @group Unit
     */
    public function testSharePropertyGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security.json');
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
        
        // issuer
        $body = file_get_contents(__DIR__ . '/Response/security_search.json');
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
        
        $this->assertIsArray( $security->getDates());
        $this->assertEquals(2, Client::getInstance()->getCounter());
        
        $indices = $security->getIndices();
        $this->assertIsArray($indices);
        
        foreach ($indices as $index) {
            $this->assertInstanceOf(Security::class, $index);
        }
        
        $this->assertEquals(3, Client::getInstance()->getCounter());
        
        $issuer = $security->getIssuer();
        $this->assertInstanceOf(Issuer::class, $issuer);
        $this->assertEquals('Публичное акционерное общество "Московская Биржа ММВБ-РТС"', $issuer->getTitle());
        $this->assertEquals('7702077840', $issuer->getInn());
        $this->assertEquals('11538317', $issuer->getOkpo());
    }

    /**
     * @group Unit
     */
    public function testCurrencyPairMarketDataGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/selt_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // market data
        $body = file_get_contents(__DIR__ . '/Response/selt_market_security_market_data.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#USD000UTSTOM';
        $security = new Security($security_name);
        
        $this->assertEquals(58.11, $security->getLastPrice());
        $this->assertEquals(57.6575, $security->getOpeningPrice());
        $this->assertEquals(57.6242, $security->getClosingPrice());
        
        $this->assertEquals(147457618258, $security->getVolume());
        $this->assertEquals(2552051000, $security->getVolume('USD'));
        $this->assertEquals(147457618258, $security->getVolume('RUB'));
        $this->assertEquals(2552051000, $security->getVolume('usd'));
        $this->assertEquals(147457618258, $security->getVolume('rub'));
        
        $this->assertEquals(58.16, $security->getDailyHigh());
        $this->assertEquals(57.5025, $security->getDailyLow());
        
        $this->assertEquals(0.42, $security->getDailyChange());
        $this->assertEquals(0.73, $security->getDailyPercentageChange());
        $this->assertEquals(0.42, $security->getChange());
        $this->assertEquals(0.42, $security->getChange('day'));
        $this->assertEquals(0.73, $security->getChange('day', '%'));
        
        $this->assertInstanceOf(\DateTime::class, $security->getLastUpdate());
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testFuturesContractMarketDataGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/forts_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // market data
        $body = file_get_contents(__DIR__ . '/Response/forts_market_security_market_data.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#SiZ7';
        $security = new Security($security_name);
        
        $this->assertEquals(58358, $security->getLastPrice());
        $this->assertEquals(58883, $security->getOpeningPrice());
        $this->assertEquals(58358, $security->getClosingPrice());
        
        $this->assertEquals(62097497536, $security->getVolume());
        $this->assertEquals(1066523843, $security->getVolume('USD'));
        $this->assertEquals(62097497536, $security->getVolume('RUB'));
        $this->assertEquals(1066523843, $security->getVolume('usd'));
        $this->assertEquals(62097497536, $security->getVolume('rub'));
        
        $this->assertEquals(58883, $security->getDailyHigh());
        $this->assertEquals(58346, $security->getDailyLow());
        
        $this->assertEquals(1, $security->getDailyChange());
        $this->assertEquals(0, $security->getDailyPercentageChange());
        $this->assertEquals(1, $security->getChange());
        $this->assertEquals(1, $security->getChange('day'));
        $this->assertEquals(0, $security->getChange('day', '%'));
        
        $this->assertInstanceOf(\DateTime::class, $security->getLastUpdate());
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testBondMarketDataGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/bonds_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // market data
        $body = file_get_contents(__DIR__ . '/Response/bonds_market_security_market_data.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#RU000A0JVBS1';
        $security = new Security($security_name);
        
        $this->assertEquals(98.6, $security->getLastPrice());
        $this->assertEquals(97.79, $security->getOpeningPrice());
        $this->assertEquals(14.37, $security->getYield());
        $this->assertEquals(240, $security->getDuration());
        
        $this->assertEquals(467437, $security->getVolume());
        $this->assertEquals(8028, $security->getVolume('USD'));
        $this->assertEquals(467437, $security->getVolume('RUB'));
        $this->assertEquals(8028, $security->getVolume('usd'));
        $this->assertEquals(467437, $security->getVolume('rub'));
        
        $this->assertEquals(98.6, $security->getDailyHigh());
        $this->assertEquals(97.12, $security->getDailyLow());
        
        $this->assertEquals(1.53, $security->getDailyChange());
        $this->assertEquals(1.58, $security->getDailyPercentageChange());
        $this->assertEquals(1.53, $security->getChange());
        $this->assertEquals(1.53, $security->getChange('day'));
        $this->assertEquals(1.58, $security->getChange('day', '%'));
        
        $this->assertInstanceOf(\DateTime::class, $security->getLastUpdate());
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testIndexMarketDataGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/index_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // market data
        $body = file_get_contents(__DIR__ . '/Response/index_market_security_market_data.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#RTSI';
        $security = new Security($security_name);
        
        $this->assertEquals(1125.69, $security->getValue());
        $this->assertEquals(1116.82, $security->getOpeningValue());
        $this->assertEquals(1118.38, $security->getPreviousClose());
        
        $this->assertEquals(31569273506, $security->getVolume());
        $this->assertEquals(549680901, $security->getVolume('USD'));
        $this->assertEquals(31569273506, $security->getVolume('RUB'));
        $this->assertEquals(549680901, $security->getVolume('usd'));
        $this->assertEquals(31569273506, $security->getVolume('rub'));
        
        $this->assertEquals(1127.45, $security->getDailyHigh());
        $this->assertEquals(1112.43, $security->getDailyLow());
        
        $this->assertEquals(731, $security->getDailyChange());
        $this->assertEquals(0.65, $security->getDailyPercentageChange());
        $this->assertEquals(731, $security->getChange());
        $this->assertEquals(731, $security->getChange('day'));
        $this->assertEquals(0.65, $security->getChange('day', '%'));
        $this->assertEquals(2511, $security->getChange('MTD'));
        $this->assertEquals(2.28, $security->getChange('MTD', '%'));
        $this->assertEquals(-2664, $security->getChange('YTD'));
        $this->assertEquals(-2.31, $security->getChange('YTD', '%'));
        
        $this->assertEquals(9511542707105, $security->getCapitalization());
        $this->assertEquals(165613990582, $security->getCapitalization('USD'));
        $this->assertEquals(9511542707105, $security->getCapitalization('RUB'));
        $this->assertEquals(165613990582, $security->getCapitalization('usd'));
        $this->assertEquals(9511542707105, $security->getCapitalization('rub'));
        
        $this->assertInstanceOf(\DateTime::class, $security->getLastUpdate());
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
    }

    /**
     * @group Unit
     */
    public function testShareMarketDataGetters()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // market data
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security_market_data.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $this->assertEquals(106.8, $security->getLastPrice());
        $this->assertEquals(105.97, $security->getOpeningPrice());
        $this->assertEquals(106.8, $security->getClosingPrice());
        
        $this->assertEquals(614837254, $security->getVolume());
        $this->assertEquals(10222039, $security->getVolume('USD'));
        $this->assertEquals(614837254, $security->getVolume('RUB'));
        $this->assertEquals(10222039, $security->getVolume('usd'));
        $this->assertEquals(614837254, $security->getVolume('rub'));
        
        $this->assertEquals(107.88, $security->getDailyHigh());
        $this->assertEquals(105.32, $security->getDailyLow());
        
        $this->assertEquals(1.23, $security->getDailyChange());
        $this->assertEquals(1.17, $security->getDailyPercentageChange());
        $this->assertEquals(1.23, $security->getChange());
        $this->assertEquals(1.23, $security->getChange('day'));
        $this->assertEquals(1.17, $security->getChange('day', '%'));
        
        $this->assertInstanceOf(\DateTime::class, $security->getLastUpdate());
        
        $this->assertEquals(2, Client::getInstance()->getCounter());
    }
    
    /**
     * @group Unit
     */
    public function testUnknownGetterThrowsException()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security.json');
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
    public function testInvalidRangeThrowsException()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        // market data
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security_market_data.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);
        
        $this->mock_handler->append($response);
        
        $security_name = '#moex';
        $security = new Security($security_name);
        
        $this->expectException(InvalidArgumentException::class);
        
        $range = 'Invalid range';
        $change = $security->getChange($range);
    }
    
    /**
     * @group Unit
     */
    public function testHistoricalQuotes()
    {
        // security
        $body = file_get_contents(__DIR__ . '/Response/shares_market_security.json');
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
        
        $this->assertIsArray($data);
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
