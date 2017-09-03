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
    
    const AUTH_URL = 'https://passport.moex.com/authenticate';
    
    const TIMEZONE = 'Europe/Moscow';
    
    const DATE_FORMAT = 'Y-m-d';
    
    /**
     * @var int The name of the cookie that holds the authentication certificate
     */
    const AUTH_CERT_COOKIE = 'MicexPassportCert';
    
    /**
     * @var \Panychek\MoEx\Client
     */
    private static $instance = null;
    
    /**
     * @var \GuzzleHttp\Client
     */
    private $client = null;
    
    /**
     * @var array Request options for the Guzzle client
     */
    private $request_options = array();
    
    /**
     * @var array
     */
    private static $extra_options = array();
    
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
        
        if (isset(self::$extra_options)) {
            $options = array_merge($options, self::$extra_options);
        }
        
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
     * Destroy an instance (for unit tests)
     * 
     * @return void
     */
    public static function destroyInstance() {
        if (isset(self::$instance)) {
            self::$instance = null;
        }
    }
    
    /**
     * Set an option for the Guzzle client (e.g. a mock handler for unit tests)
     * 
     * @param  string $option
     * @param  mixed  $value
     * @return void
     */
    public static function setExtraOption($option, $value) {
        self::$extra_options[$option] = $value;
    }
    
    /**
     * Set a request option for the Guzzle client
     * 
     * @see    http://guzzle.readthedocs.io/en/latest/request-options.html
     * @param  string $option
     * @param  mixed  $value
     * @return void
     */
    public function setRequestOption($option, $value) {
        $this->request_options[$option] = $value;
    }
    
    /**
     * Set the language
     *
     * @param  string $lang
     * @throws Exception\InvalidArgumentException for any language except Russian and English
     * @return void
     */
    public function setLanguage($lang)
    {
        $langs = array('ru', 'en');
        if (!in_array($lang, $langs)) {
            $message = 'Unsupported language. Available languages: "ru" and "en"';
            throw new Exception\InvalidArgumentException($message);
        }
        
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
    protected function request(string $relative_uri, array $params = array())
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
            
            if (!empty($this->request_options)) {
                $options = array_merge($options, $this->request_options);
            }
            
            $response = $this->doRequest($uri, $options);
            
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
     * Make a request through the Guzzle client
     * 
     * @param  string $uri
     * @param  array  $options
     * @return void
     */
    protected function doRequest(string $uri, array $options) {
        return $this->client->get($uri, $options);
    }
    
    /**
     * Authenticate a user against the Moscow Exchange Passport
     * 
     * @param  string $username
     * @param  array  $password
     * @throws Exception\AuthenticationException if the authentication fails
     * @return true
     */
    public function authenticate(string $username, string $password) {
        try {
            $options =  array(
                'auth' => array(
                    $username,
                    $password
                )
            );
            
            $response = $this->doRequest(self::AUTH_URL, $options);
            
            $cookies = $response->getHeader('Set-Cookie');
        
            $this->request_options['cookies'] = new \GuzzleHttp\Cookie\CookieJar;
            
            $cert_found = false;
            foreach ($cookies as $cookie_str) {
               $cookie = \GuzzleHttp\Cookie\SetCookie::fromString($cookie_str);
                
               if ($cookie->getName() == self::AUTH_CERT_COOKIE) {
                   $cert_found = true;
               }
               
               $this->request_options['cookies']->setCookie($cookie);
            }
            
            if (!$cert_found) {
                $message = 'Authentication certificate not found';
                throw new Exception\AuthenticationException($message);
            }
            
            return true;
            
        } catch (TransferException $e) {
            $message = 'Authentication failed. ' . $e->getMessage();
            throw new Exception\AuthenticationException($message, $e->getCode(), $e);
        }
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
        return $this->getData($uri);
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
     * Get the turnovers
     *
     * @param  \DateTime|false $date
     * @return array
     */
    public function getTurnovers($date = false)
    {
        $uri = 'turnovers';
        
        $params = array();
        if ($date) {
            $params['date'] = $date->format(self::DATE_FORMAT);
        }
        
        return $this->getData($uri, $params);
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
     * Get the security groups
     *
     * @return array
     */
    public function getSecurityGroups()
    {
        $uri = 'securitygroups';
        return $this->getData($uri);
    }
    
    /**
     * Get the security group collections
     * 
     * @param  string $security_group
     * @return array
     */
    public function getSecurityGroupCollections(string $security_group)
    {
        $uri = sprintf('securitygroups/%s/collections', $security_group);
        return $this->getData($uri);
    }
    
    /**
     * Get the collection
     * 
     * @param  string $security_group
     * @param  string $collection
     * @return array
     */
    public function getCollection(string $security_group, string $collection)
    {
        $uri = sprintf('securitygroups/%s/collections/%s', $security_group, $collection);
        return $this->getData($uri);
    }
    
    /**
     * Get the collection securities
     * 
     * @param  string $security_group
     * @param  string $collection
     * @return array
     */
    public function getCollectionSecurities(string $security_group, string $collection)
    {
        $uri = sprintf('securitygroups/%s/collections/%s/securities', $security_group, $collection);
        
        $params = array(
            'start' => 0,
            'limit' => 20
        );
        
        return $this->fetchAllPages($uri, 'securities', $params);
    }
    
    /**
     * Get the index list
     *
     * @return array
     */
    public function getIndexList()
    {
        $uri = 'statistics/engines/stock/markets/index/analytics';
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
            'start' => 0,
            'limit' => 100
        );
        
        if ($from) {
            $params['from'] = $from->format(self::DATE_FORMAT);
        }
        
        if ($to) {
            $params['till'] = $to->format(self::DATE_FORMAT);
        }
        
        return $this->fetchAllPages($uri, 'history', $params);
    }
    
    /**
     * Recursively fetch all the pages
     *
     * @param  string $uri
     * @param  string $field
     * @param  array  $params
     * @return void
     */
    private function fetchAllPages(string $uri, string $field, array $params)
    {
        $data = $this->getData($uri, $params);
        
        if (empty($data)) {
            return array();
        }
        
        if (!empty($data[$field . '.cursor'])) { // there is a cursor
            $cursor = $data[$field . '.cursor'][0];
            if ($cursor['INDEX'] + $cursor['PAGESIZE'] < $cursor['TOTAL']) { // keep going
                $params['start'] += $params['limit'];
                $next_page = $this->fetchAllPages($uri, $field, $params);
                $data[$field] = array_merge($data[$field], $next_page[$field]);
            }
            
        } else {
            if (count($data[$field]) == $params['limit']) { // keep going
                $params['start'] += $params['limit'];
                $next_page = $this->fetchAllPages($uri, $field, $params);
                $data[$field] = array_merge($data[$field], $next_page[$field]); 
            }
        }
        
        
        return $data;
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
