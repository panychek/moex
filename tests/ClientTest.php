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
use GuzzleHttp\Exception\TransferException;
use Panychek\MoEx\Exchange;
use Panychek\MoEx\Engine;
use Panychek\MoEx\Market;
use Panychek\MoEx\Board;
use Panychek\MoEx\Client;
use Panychek\MoEx\Exception\DataException;
use Panychek\MoEx\Exception\InvalidArgumentException;

class ClientTest extends TestCase
{
    protected function tearDown()
    {
        Client::destroyInstance();
    }
    
    /**
     * @group Unit
     */
    public function testFailedRequestThrowsException()
    {
        $mock = $this->getMockBuilder(Client::class)->setMethods(['doRequest'])->getMock();
        $mock->method('doRequest')->will($this->throwException(new TransferException));
        
        $this->expectException(DataException::class);
        $this->expectExceptionCode(DataException::FAILED_REQUEST);
        
        $reflection = new \ReflectionClass($mock);
        $method = $reflection->getMethod('request');
        $method->setAccessible(true);
        
        $response = $method->invokeArgs($mock, array('invalid_uri'));
    }
    
    /**
     * @group Unit
     */
    public function testInvalidJsonResponseThrowsException()
    {
        $mock = $this->getMockBuilder(Client::class)->setMethods(array('request'))->getMock();
        $mock->method('request')->willReturn('Invalid JSON string');
        
        $this->expectException(DataException::class);
        $this->expectExceptionCode(DataException::INVALID_RESPONSE);
        
        $reflection = new \ReflectionClass(Client::class);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        
        $response = $method->invokeArgs($mock, array('uri'));
    }
    
    /**
     *
     * @group Unit
     * @dataProvider responseProvider
     */
    public function testUnsupportedResponseFormatThrowsException($response, $expected_status)
    {
        $mock = $this->getMockBuilder(Client::class)->setMethods(array('request'))->getMock();
        
        $response = json_encode($response);
        $mock->method('request')->willReturn($response);
        
        if (!$expected_status) {
            $this->expectException(DataException::class);
            $this->expectExceptionCode(DataException::INVALID_RESPONSE);
        }
        
        $reflection = new \ReflectionClass(Client::class);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        
        $response = $method->invokeArgs($mock, array('uri'));
        
        $this->assertInternalType('array', $response);
    }
    
    public function responseProvider()
    {
        return array(
            array(
                array(),
                true
            ),
            array(
                array(
                    'section' => array()
                ),
                false
            ),
            array(
                array(
                    'section' => array(
                        'columns' => array()
                    )
                ),
                false
            ),
            array(
                array(
                    'section' => array(
                        'data' => array()
                    )
                ),
                false
            ),
            array(
                array(
                    'section' => array(
                        'columns' => array(),
                        'data' => array()
                    )
                ),
                true
            )
        );
    }
    
    /**
     * @group Unit
     */
    public function testUnsupportedLanguageThrowsException()
    {
        $client = Client::getInstance();
        
        $this->expectException(InvalidArgumentException::class);
        $client->setLanguage('invalid_language');
    }
    
    /**
     *
     * @group Integration
     */
    public function testEngineListSuccessfulResponse()
    {
        $client = Client::getInstance();
        $data = $client->getEngineList();
        
        $this->assertInternalType('array', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testEngineSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $engine_id = 'stock';
        $data = $client->getEngine($engine_id);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('engine', $data);
        $this->assertArrayHasKey('timetable', $data);
        $this->assertArrayHasKey('dailytable', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testTurnoversSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $data = $client->getTurnovers();
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('turnovers', $data);
        $this->assertArrayHasKey('turnoversprevdate', $data);
        $this->assertArrayHasKey('turnoverssectors', $data);
        $this->assertArrayHasKey('turnoverssectorsprevdate', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testMarketListSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $engine_id = 'stock';
        $data = $client->getMarketList($engine_id);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('markets', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testMarketSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $engine_id = 'stock';
        $market_id = 'shares';
        $data = $client->getMarket($engine_id, $market_id);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('boards', $data);
        $this->assertArrayHasKey('boardgroups', $data);
        $this->assertArrayHasKey('securities', $data);
        $this->assertArrayHasKey('marketdata', $data);
        $this->assertArrayHasKey('trades', $data);
        $this->assertArrayHasKey('orderbook', $data);
        $this->assertArrayHasKey('history', $data);
        $this->assertArrayHasKey('trades_hist', $data);
    }

    /**
     *
     * @group Integration
     */
    public function testBoardSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $engine_id = 'stock';
        $market_id = 'shares';
        $board_id = 'TQBR';
        $data = $client->getBoard($engine_id, $market_id, $board_id);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('board', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testSecuritySearchSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $string = 'MoscowExchange';
        $data = $client->findSecurity($string);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('securities', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testSecuritySuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $security_code = 'moex';
        $data = $client->getSecurity($security_code);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('boards', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testSecurityIndicesSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $security_code = 'moex';
        $data = $client->getSecurityIndices($security_code);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('indices', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testSecurityGroupsSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $data = $client->getSecurityGroups();
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('securitygroups', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testSecurityGroupCollectionsSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $security_group = 'stock_shares';
        $data = $client->getSecurityGroupCollections($security_group);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('collections', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testCollectionSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $security_group = 'stock_shares';
        $collection = 'stock_shares_one';
        $data = $client->getCollection($security_group, $collection);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('collections', $data);
        $this->assertArrayHasKey('boardgroups', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testCollectionSecuritiesSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $security_group = 'stock_shares';
        $collection = 'stock_shares_one';
        $data = $client->getCollectionSecurities($security_group, $collection);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('securities', $data);
    }

    /**
     *
     * @group Integration
     */
    public function testMarketDataSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $engine_id = 'stock';
        $market_id = 'shares';
        $security_code = 'moex';
        $data = $client->getMarketData($engine_id, $market_id, $security_code);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('securities', $data);
        $this->assertArrayHasKey('marketdata', $data);
        $this->assertArrayHasKey('dataversion', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testSecurityDatesSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $engine_id = 'stock';
        $market_id = 'shares';
        $board_id = 'TQBR';
        $security_code = 'moex';
        $data = $client->getSecurityDates($engine_id, $market_id, $board_id, $security_code);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('dates', $data);
    }
    
    /**
     * @group Integration
     */
    public function testHistoricalQuotesSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $engine_id = 'stock';
        $market_id = 'shares';
        $board_id = 'TQBR';
        $security_code = 'moex';
        $from = new \DateTime('01/01/2014');
        $to = new \DateTime('12/31/2014');
        
        $data = $client->getHistoricalQuotes($engine_id, $market_id, $board_id, $security_code, $from, $to);
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('history', $data);
    }
    
    /**
     *
     * @group Integration
     */
    public function testCapitalizationSuccessfulResponse()
    {
        $client = Client::getInstance();
        
        $data = $client->getCapitalization();
        
        $this->assertInternalType('array', $data);
        
        $this->assertArrayHasKey('capitalization', $data);
        $this->assertArrayHasKey('issuecapitalization', $data);
    }
}
