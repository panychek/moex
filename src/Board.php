<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

class Board extends AbstractEntry
{
    /**
     * @var array
     */
    private static $instances = array();
    
    /**
     * The market the board belongs to
     *
     * @var \Panychek\MoEx\Market
     */
    private $market = null;
    
    /**
     * The engine the board belongs to
     *
     * @var \Panychek\MoEx\Engine
     */
    private $engine = null;
    
    /**
     * Constructor
     * 
     * @param  string $id        Board ID, e.g. 'CETS', 'EQBR', etc.
     * @param  string $engine_id Engine ID, e.g. 'stock', 'commodity', etc.
     * @param  string $market_id Market ID, e.g. 'bonds', 'futures', etc.
     * @throws Exception\InvalidArgumentException for invalid board ids
     * @return void
     */
    public function __construct(string $id, string $engine_id, string $market_id)
    {
        if ($id === '') {
            $message = 'Invalid board id';
            throw new Exception\InvalidArgumentException($message);
        }
        
        $this->setId($id);
        $this->setEngine($engine_id);
        $this->setMarket($market_id);
    }
    
    /**
     * Get an instance
     *
     * @param  string $id        Board ID, e.g. 'CETS', 'EQBR', etc.
     * @param  string $engine_id Engine ID, e.g. 'stock', 'commodity', etc.
     * @param  string $market_id Market ID, e.g. 'bonds', 'futures', etc.
     * @return \Panychek\MoEx\Board
     */
    public static function getInstance(string $id, string $engine_id, string $market_id)
    {
        if (!isset(self::$instances[$engine_id][$market_id][$id])) {
            self::$instances[$engine_id][$market_id][$id] = new self($id, $engine_id, $market_id);
        }
        
        return self::$instances[$engine_id][$market_id][$id];
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
        }
        
        $message = sprintf('Method "%s" does not exist', $name);
        throw new Exception\BadMethodCallException($message);
    }

    /**
     * Load the info
     *
     * @throws Exception\DataException for unknown boards
     * @return void
     */
    private function loadInfo()
    {
        if(empty($this->getProperties())) { // haven't been loaded yet
            $engine = $this->getEngine();
            $market = $this->getMarket();
            
            $board = Client::getInstance()->getBoard($engine->getId(), $market->getId(), $this->getId());
            
            if (empty($board['board'])) {
                $message = sprintf('Board "%s" not found', $this->getId());
                throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
            }
            
            $properties = $board['board'][0];
            $this->setProperties($properties);
        }
    }
    
    /**
     * Set the engine
     *
     * @param  string $engine_id
     * @return void
     */
    public function setEngine(string $engine_id)
    {
        $this->engine = Engine::getInstance($engine_id);
    }
    
    /**
     * Get the engine
     *
     * @return \Panychek\MoEx\Engine
     */
    public function getEngine()
    {
        return $this->engine;
    }
    
    /**
     * Set the market
     *
     * @param  string $market_id
     * @return void
     */
    public function setMarket(string $market_id)
    {
        $engine_id = $this->getEngine()->getId();
        $this->market = Market::getInstance($market_id, $engine_id);
    }
    
    /**
     * Get the market
     *
     * @return \Panychek\MoEx\Market
     */
    public function getMarket()
    {
        return $this->market;
    }
}
