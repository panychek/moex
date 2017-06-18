<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

class Market extends AbstractEntry
{
    /**
     * @var array
     */
    private static $instances = array();
    
    /**
     * The engine the market belongs to
     *
     * @var \Panychek\MoEx\Engine
     */
    private $engine = null;
    
    /**
     * @var array
     */
    private $boards = array();
    
    /**
     * Constructor
     * 
     * @param  string $id        Market ID, e.g. 'bonds', 'futures', etc.
     * @param  string $engine_id Engine ID, e.g. 'stock', 'commodity', etc.
     * @throws Exception\InvalidArgumentException for invalid market ids
     * @return void
     */
    public function __construct(string $id, string $engine_id)
    {
        if ($id === '') {
            $message = 'Invalid market id';
            throw new Exception\InvalidArgumentException($message);
        }
        
        $this->setClient();
        $this->setId($id);
        $this->setEngine($engine_id);
    }
    
    /**
     * Get an instance
     *
     * @param  string $id        Market ID, e.g. 'bonds', 'futures', etc.
     * @param  string $engine_id Engine ID, e.g. 'stock', 'commodity', etc.
     * @return \Panychek\MoEx\Market
     */
    public static function getInstance(string $id, string $engine_id)
    {
        if (!isset(self::$instances[$engine_id][$id])) {
            self::$instances[$engine_id][$id] = new self($id, $engine_id);
        }
        
        return self::$instances[$engine_id][$id];
    }
    
    /**
     * Load the info
     *
     * @throws Exception\DataException for unknown markets
     * @return void
     */
    private function loadInfo()
    {
        if(empty($this->boards)) { // haven't been loaded yet
            $market = $this->client->getMarket($this->getEngine()->getId(), $this->getId());
            
            if (empty($market)) {
                $message = sprintf('Market "%s" not found', $this->getId());
                throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
            }
            
            $this->setBoards($market['boards']);
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
     * Get the title
     *
     * @return string
     */
    public function getTitle()
    {
        if(empty($this->getProperties()['title'])) {
            $markets = $this->getEngine()->getMarkets();
            $title = $markets[$this->getId()];
            
            $this->setProperty('title', $title);
        }
        
        return $this->getProperties()['title'];
    }
    
    /**
     * Set the boards
     *
     * @param  array $boards
     * @return void
     */
    public function setBoards(array $boards)
    {
        $this->boards = $boards;
    }
    
    /**
     * Get the boards
     *
     * @return array
     */
    public function getBoards()
    {
        $this->loadInfo();
        return $this->boards;
    }
}
