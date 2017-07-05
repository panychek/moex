<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

abstract class Engine extends AbstractEntry
{
    /**
     * @var array
     */
    private static $instances = array();
    
    /**
     * Get an instance
     *
     * @param  string $id Engine ID, e.g. 'stock', 'commodity', etc.
     * @return \Panychek\MoEx\Engine
     */
    public static function getInstance(string $id)
    {
        if (!isset(self::$instances[$id])) {
            $class = 'Panychek\MoEx\\' . ucwords($id) . '\Engine';
            self::$instances[$id] = new $class;
        }
        
        return self::$instances[$id];
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
     * @throws Exception\DataException for unknown engines
     * @return void
     */
    private function loadInfo()
    {
        if(empty($this->getProperties())) { // haven't been loaded yet
            $engine = Client::getInstance()->getEngine($this->getId());
            
            if (empty($engine)) {
                $message = sprintf('Engine "%s" not found', $this->getId());
                throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
            }
            
            $properties = $engine['engine'][0];
            $this->setProperties($properties);
        }
    }
    
    /**
     * Get the markets
     *
     * @throws Exception\DataException when the list is empty
     * @return array
     */
    public function getMarkets()
    {
        $markets = Client::getInstance()->getMarketList($this->getId());
        
        if (empty($markets['markets'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        $data = array();
        foreach ($markets['markets'] as $v) {
            $data[$v['NAME']] = $v['title'];
        }
        
        return $data;
    }
}
