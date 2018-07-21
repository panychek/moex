<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

class Security extends AbstractEntry
{
    use ValidationTrait;
    
    /**
     * @var \Panychek\MoEx\Board
     */
    private $board = null;
    
    /**
     * @var array
     */
    private $available_boards = array();
    
    /**
     * The issuer of this security
     *
     * @var \Panychek\MoEx\Issuer|false|null
     */
    private $issuer = null;
    
    /**
     * @var array
     */
    private $market_data = array();
    
    /**
     * @var array
     */
    private $history_data = array();
    
    /**
     * Constructor
     * 
     * @param  string $name
     * @throws Exception\InvalidArgumentException for invalid names
     * @return void
     */
    public function __construct(string $name)
    {
        if ($name === '') {
            $message = 'Security name can not be empty';
            throw new Exception\InvalidArgumentException($message);
        }
        
        $code = $this->getCodeByString($name);
        $this->setId($code);
    }
    
    /**
     * Call a method
     * 
     * @param  string $name      Method name to call
     * @param  array  $arguments Method arguments
     * @throws Exception\BadMethodCallException
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if ($this->isGetterMethod($name)) {
            
            $this->loadInfo();
            
            $property = $this->getPropertyFromMethod($name);
            if (isset($this->getProperties()[$property])) {
                return $this->getProperties()[$property];                
            }
            
            $mapped_property = $this->getMarket()->getMappedProperty($property);
            if ($mapped_property) {
                return $this->getProperties()[$mapped_property];
            }
            
            $market_data_getter = $this->getMarket()->getMarketDataGetter($property);
            if ($market_data_getter) { // market data

                if (empty($this->getMarketData())) { // haven't been loaded yet
                    $this->setMarketData();
                }
                
                return $market_data_getter($this->getMarketData(), $arguments);
            }
        }
        
        $message = sprintf('Method "%s" does not exist', $name);
        throw new Exception\BadMethodCallException($message);
    }
    
    /**
     * Load the security specification
     *
     * @throws Exception\DataException for unknown securities
     * @return void
     */
    private function loadInfo()
    {
        if(empty($this->getProperties())) { // haven't been loaded yet
            $security = Client::getInstance()->getSecurity($this->getId());
        
            if (empty($security)) {
                $message = sprintf('Security "%s" not found', $this->getId());
                throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
            }
            
            $description = array();
            foreach ($security['description'] as $v) {
                $description[$v['name']] = $v['value'];
            }
            $this->setProperties($description);
            
            $this->setAvailableBoards($security['boards']);
            $this->setBoard($this->getAvailableBoards()[0]);
        }
    }
    
    /**
     * Set the current board
     *
     * @param  Panychek\MoEx\Board $board
     * @return void
     */
    private function setBoard(Board $board)
    {
        $this->board = $board;
    }
    
    /**
     * Get the current board
     *
     * @return \Panychek\MoEx\Board
     */
    public function getBoard()
    {
        $this->loadInfo();
        return $this->board;
    }
    
    /**
     * Set the available boards
     *
     * @param  array $data
     * @return void
     */
    private function setAvailableBoards(array $data)
    {
        foreach ($data as $row) {
            $board = Board::getInstance($row['boardid'], $row['engine'], $row['market']);
            $board->setProperties($row);
            
            $this->available_boards[] = $board;
        }
    }
    
    /**
     * Get the available boards
     *
     * @return array
     */
    public function getAvailableBoards()
    {
        $this->loadInfo();
        return $this->available_boards;
    }
    
    /**
     * Get the exchange
     *
     * @return \Panychek\MoEx\Exchange
     */
    public function getExchange()
    {
        return Exchange::getInstance();
    }
    
    /**
     * Get the engine
     *
     * @return \Panychek\MoEx\Engine
     */
    public function getEngine()
    {
        return $this->getBoard()->getEngine();
    }
    
    /**
     * Get the market
     *
     * @return \Panychek\MoEx\Market
     */
    public function getMarket()
    {
        return $this->getBoard()->getMarket();
    }
    
    /**
     * Get the security code by a string
     *
     * @param  string $name
     * @return string
     */
    private function getCodeByString(string $name)
    {
        if ($name[0] == '#') { // it's a security code
            $security_code = substr($name, 1);
            
        } else { // best match
            $securities = Exchange::getInstance()->findSecurities($name, 1);
            $security = $securities[0];
            $this->setIssuer($security);
            $security_code = $security['secid'];
        }
        
        return $security_code;
    }
    
    /**
     * Set the issuer
     *
     * @param  array $data
     * @return void
     */
    public function setIssuer(array $data)
    {
        $issuer_id = $data['emitent_id'];
        if ($issuer_id) {
            $issuer = Issuer::getInstance($data['emitent_id']);
        
            $issuer->setProperty('title', $data['emitent_title']);
            $issuer->setProperty('inn', $data['emitent_inn']);
            $issuer->setProperty('okpo', $data['emitent_okpo']);
            
        } else {
            $issuer = false;
        }
        
        $this->issuer = $issuer;
    }
    
    /**
     * Get the issuer
     *
     * @return \Panychek\MoEx\Issuer
     */
    public function getIssuer()
    {
        if (is_null($this->issuer)) {
            $search_string = sprintf('"%s"', $this->getId());
            $securities = Exchange::getInstance()->findSecurities($search_string);
            
            foreach ($securities as $security) {
                if (strtolower($security['secid']) == strtolower($this->getId())) {
                    $this->setIssuer($security);
                    break;
                }
            }
        }
        
        return $this->issuer;
    }
    
    /**
     * Set the market data
     *
     * @throws Exception\DataException for unsupported data formats
     * @return void
     */
    private function setMarketData()
    {
        $engine = $this->getEngine();
        $market = $this->getMarket();
        
        $market_data = Client::getInstance()->getMarketData($engine->getId(), $market->getId(), $this->getId());
        
        if (empty($market_data['marketdata'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        // sort
        $sort = array();
        foreach ($market_data['marketdata'] as $k => $v) {
            $sort[$k] = $v['VALTODAY'];
        }
        
        array_multisort($sort, SORT_DESC, $market_data['marketdata']);
        
        $this->market_data = array_change_key_case($market_data['marketdata'][0]);
    }
    
    /**
     * Get the market data
     *
     * @return array
     */
    public function getMarketData()
    {
        return $this->market_data;
    }
    
    /**
     * Refresh the data
     * 
     * @return void
     */
    public function refresh()
    {
        $this->setMarketData();
    }
    
    /**
     * Get the indices this security is a component of
     *
     * @return array
     */
    public function getIndices()
    {
        $indices = Client::getInstance()->getSecurityIndices($this->getId());
        
        $data = array();
        if (!empty($indices['indices'])) {
            foreach ($indices['indices'] as $row) {
                $index = new Security('#' . $row['SECID']);
                $index->setProperties($row);
                
                $data[] = $index;
            }
        }
        
        return $data;
    }
    
    /**
     * Get the date interval
     *
     * @throws Exception\DataException when no data is available for this security
     * @return array
     */
    public function getDates()
    {
        $engine = $this->getEngine();
        $market = $this->getMarket();
        $board = $this->getBoard();
        
        $dates = Client::getInstance()->getSecurityDates($engine->getId(), $market->getId(), $board->getId(), $this->getId());
        
        if (empty($dates['dates'])) {
            $message = sprintf('No available data for "%s"', $this->getId());
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        return $dates['dates'][0];
    }
    
    /**
     * Get the historical quotes
     *
     * @param  \DateTime|string|false $from
     * @param  \DateTime|string|false $to
     * @throws Exception\InvalidArgumentException
     * @return array
     */
    public function getHistoricalQuotes($from = false, $to = false)
    {
        $args = array($from, $to);
        
        for ($i = 0;$i <= 1;$i ++) {
            if (isset($args[$i])) {
                $date = $args[$i];
                
                $this->validateDate($date);
                
                if (is_string($date)) {
                    $timezone = new \DateTimeZone(Client::TIMEZONE);
                    $args[$i] = new \DateTime($date, $timezone);
                }
            }
        }
        
        list($from, $to) = $args;
        
        if ($from && $to) { // in case already loaded
            $from_str = $from->format(Client::DATE_FORMAT);
            $to_str = $to->format(Client::DATE_FORMAT);
            
            $days_between = $to->diff($from)->format('%a');
            
            $key = array_search($from_str, array_keys($this->history_data), true);
            if ($key !== false) {
                $slice = array_slice($this->history_data, $key, $days_between + 1, true);
                
                end($slice);
                $last_key = key($slice);
                
                if ($last_key == $to_str) { // got the whole range
                    $data = array_filter($slice);
                    return $data;
                }
            }
        }
        
        return $this->setHistoryData($from, $to);
    }

    /**
     * Set the history data for a given date range
     *
     * @param  \DateTime|false $from
     * @param  \DateTime|false $to
     * @return array
     */
    private function setHistoryData($from, $to)
    {
        $engine = $this->getEngine();
        $market = $this->getMarket();
        $board = $this->getBoard();
        
        $raw_data = Client::getInstance()->getHistoricalQuotes(
            $engine->getId(),
            $market->getId(),
            $board->getId(),
            $this->getId(),
            $from,
            $to
        );
        
        if (empty($raw_data['history'])) {
            return array();
        }
        
        $fields = array(
            'open', 'high', 'low', 'close', 'volume'
        );
        
        $data = array();
        foreach ($raw_data['history'] as $k => $v) {
            $row = array();
            foreach ($fields as $field) {
                $key = strtoupper($field);
                
                if (!empty($v[$key])) {
                    $row[$field] = $v[$key];
                }
            }
            
            $data[$v['TRADEDATE']] = $row;
        }
        
        if ($from && $to) { // empty values for the new dates
            $interval = \DateInterval::createFromDateString('1 day');
            $period = new \DatePeriod($from, $interval, $to);
            
            foreach ($period as $date) {
                $day = $date->format(Client::DATE_FORMAT);
                
                if (!isset($this->history_data[$day])) {
                    $this->history_data[$day] = null;
                }
            }
        }
        
        $this->history_data = array_merge($this->history_data, $data);
        
        return $data;
    }
}
