<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

use GuzzleHttp\Exception\TransferException;

class Client
{
    const API_URL = 'http://iss.moex.com/iss/';
    
    const DATE_FORMAT = 'Y-m-d';
    
    /**
     * @var \Panychek\MoEx\Client
     */
    private static $instance = null;
    
    /**
     * @var \GuzzleHttp\Client
     */
    private $client = null;
    
    /**
     * @var int Total number of successful requests made to the server
     */
    private $counter = 0;
    
    /**
     * @var string
     */
    private $lang = 'ru';
    
    /**
     * @var bool Whether to include metadata in the result set
     */
    private $metadata = false;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $options = array(
            'base_uri' => self::API_URL
        );
        
        $this->client = new \GuzzleHttp\Client($options);
    }
    
    /**
     * Get an instance
     * 
     * @return \Panychek\MoEx\Client
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * Set the language
     *
     * @param string $lang
     * @return void
     */
    public function setLanguage($lang)
    {
        $this->lang = $lang;
    }
    
    /**
     * Get the language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->lang;
    }
    
    /**
     * Increase the counter
     *
     * @return void
     */
    private function increaseCounter() {
        $this->counter ++;
    }
    
    /**
     * Get the counter
     *
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }
    
    /**
     * Execute a request
     * 
     * @param  string $relative_uri
     * @param  array  $params
     * @throws Exception\DataException if the request fails
     * @return string
     */
    private function request(string $relative_uri, array $params = array())
    {
        try {
            $uri = $relative_uri . '.json';
            
            $params['lang'] = $this->lang;
            
            if (!$this->metadata) {
                $params['iss.meta'] = 'off';
            }
            
            $options = array(
                'query' => $params
            );
            
            $response = $this->client->get($uri, $options);
            
            $this->increaseCounter();
            
        } catch (TransferException $e) {
            $message = 'MoEx ISS API is not available. ' . $e->getMessage();
            throw new Exception\DataException($message, Exception\DataException::FAILED_REQUEST, $e);
        }
        
        $body = $response->getBody();
        $contents = $body->getContents();
        
        return $contents;
    }

    /**
     * Process the response data
     * 
     * @param  string $relative_uri
     * @param  array  $params
     * @throws Exception\DataException if the response is not a valid JSON string
     * @return string
     */
    private function getData(string $relative_uri, array $params = array())
    {
        $contents = $this->request($relative_uri, $params);
        
        $result = json_decode($contents, true);
        
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Invalid JSON response';
            throw new Exception\DataException($message, Exception\DataException::INVALID_RESPONSE);
        }
        
        $data = $this->parseResult($result);
        
        return $data;
    }
    
    /**
     * Parse the result into a new array
     * 
     * @param  array $result
     * @throws Exception\DataException for unsupported response formats
     * @return array
     */
    private function parseResult(array $result)
    {
        $data = array();
        $blocks = array_keys($result);
        
        foreach ($blocks as $block_key) {
            $block = $result[$block_key];
            if (!array_key_exists('columns', $block) || !array_key_exists('data', $block)) {
                $message = 'Unsupported response format';
                throw new Exception\DataException($message, Exception\DataException::INVALID_RESPONSE);
            }
            
            foreach ($block['data'] as $k => $v) {
                $data[$block_key][$k] = array();
                
                foreach($v as $k1 => $v1) {
                    $column_name = $block['columns'][$k1];
                    $data[$block_key][$k][$column_name] = $v1;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Get the engine list
     *
     * @return array
     */
    public function getEngineList()
    {
        $uri = 'engines';
        $raw_data = $this->getData($uri);
        
        $data = array();
        foreach ($raw_data['engines'] as $v) {
            $data[$v['name']] = $v['title'];
        }
        
        return $data;
    }
    
    /**
     * Get the engine description
     *
     * @param  string $engine
     * @return array
     */
    public function getEngine(string $engine)
    {
        $uri = 'engines/' . $engine;
        return $this->getData($uri);
    }
    
    /**
     * Get the market list
     *
     * @param  string $engine
     * @return array
     */
    public function getMarketList(string $engine)
    {
        $uri = sprintf('engines/%s/markets', $engine);
        return $this->getData($uri);
    }
    
    /**
     * Get the market
     *
     * @param  string $engine
     * @param  string $market
     * @return array
     */
    public function getMarket(string $engine, string $market)
    {
        $uri = sprintf('engines/%s/markets/%s', $engine, $market);
        return $this->getData($uri);
    }
    
    /**
     * Get the board description
     *
     * @param  string $engine
     * @param  string $market
     * @param  string $board
     * @return array
     */
    public function getBoard(string $engine, string $market, string $board)
    {
        $uri = sprintf('engines/%s/markets/%s/boards/%s', $engine, $market, $board);
        return $this->getData($uri);
    }
    
    /**
     * Find the security
     * 
     * @param  string $string
     * @param  int $limit
     * @return array
     */
    public function findSecurity($string, $limit = 100)
    {
        $uri = 'securities';
        
        $params = array(
            'q' => $string,
            'limit' => $limit
        );
        
        return $this->getData($uri, $params);
    }
    
    /**
     * Get the security specification
     *
     * @param  string $security_code
     * @return array
     */
    public function getSecurity(string $security_code)
    {
        $uri = 'securities/' . $security_code;
        return $this->getData($uri);
    }
    
    /**
     * Get the security indices
     *
     * @param  string $security_code
     * @return array
     */
    public function getSecurityIndices(string $security_code)
    {
        $uri = sprintf('securities/%s/indices', $security_code);
        return $this->getData($uri);
    }
    
    /**
     * Get the market data
     *
     * @param  string $engine
     * @param  string $market
     * @param  string $security_code
     * @return array
     */
    public function getMarketData(string $engine, string $market, string $security_code)
    {
        $uri = sprintf('engines/%s/markets/%s/securities/%s', $engine, $market, $security_code);
        return $this->getData($uri);
    }
    
    /**
     * Get the security date interval
     *
     * @param  string $engine
     * @param  string $market
     * @param  string $security_code
     * @return array
     */
    public function getSecurityDates(string $engine, string $market, string $board, string $security_code)
    {
        $uri = 'history/engines/%s/markets/%s/boards/%s/securities/%s/dates';
        $uri = sprintf($uri, $engine, $market, $board, $security_code);
        
        return $this->getData($uri);
    }

    /**
     * Get the historical quotes for a given date range
     *
     * @param  string          $engine
     * @param  string          $market
     * @param  string          $board
     * @param  string          $security_code
     * @param  \DateTime|false $from
     * @param  \DateTime|false $to
     * @return array
     */
    public function getHistoricalQuotes(
        string $engine,
        string $market,
        string $board,
        string $security_code,
        $from = false,
        $to = false
    ) {
        $uri = 'history/engines/%s/markets/%s/boards/%s/securities/%s';
        $uri = sprintf($uri, $engine, $market, $board, $security_code);
        
        $params = array(
            'limit' => 100
        );
        
        if ($from) {
            $params['from'] = $from->format(self::DATE_FORMAT);
        }
        
        if ($to) {
            $params['till'] = $to->format(self::DATE_FORMAT);
        }
        
        $start = 0;
        $raw_data = $this->fetchHistoryPage($uri, $params, $start);
        
        $fields = array(
            'open', 'high', 'low', 'close', 'volume'
        );
        
        $data = array();
        foreach ($raw_data as $k => $v) {
            $row = array();
            foreach ($fields as $field) {
                $row[$field] = $v[strtoupper($field)];
            }
            
            $data[$v['TRADEDATE']] = $row;
        }
        
        return $data;
    }
    
    /**
     * Recursively fetch all the pages
     *
     * @param  string $uri
     * @param  array  $params
     * @param  int    $start
     * @return void
     */
    private function fetchHistoryPage(string $uri, array $params, int $start)
    {
        $params['start'] = $start;
        
        $raw_data = $this->getData($uri, $params);
        
        if (empty($raw_data)) {
            return array();
        }
        
        if (count($raw_data['history']) == $params['limit']) { // keep going
            $next_page = $this->fetchHistoryPage($uri, $params, $start + $params['limit']);
            $raw_data['history'] = array_merge($raw_data['history'], $next_page); 
        }
        
        return $raw_data['history'];
    }
    
    /**
     * Get the stock market capitalization
     *
     * @return float
     */
    public function getCapitalization()
    {
        $uri = 'statistics/engines/stock/capitalization';
        return $this->getData($uri);
    }
}
