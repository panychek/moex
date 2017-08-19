<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

class SecurityGroup extends AbstractEntry
{
    /**
     * @var array
     */
    private static $instances = array();
    
    /**
     * @var array
     */
    private $collections = array();
    
    /**
     * Constructor
     * 
     * @param  string $id
     * @throws Exception\InvalidArgumentException for invalid security group ids
     * @return void
     */
    public function __construct(string $id)
    {
        if ($id === '') {
            $message = 'Invalid security group id';
            throw new Exception\InvalidArgumentException($message);
        }
        
        $this->setId($id);
    }
    
    /**
     * Get an instance
     *
     * @param  string $id
     * @return \Panychek\MoEx\SecurityGroup
     */
    public static function getInstance(string $id)
    {
        if (!isset(self::$instances[$id])) {
            self::$instances[$id] = new self($id);
        }
        
        return self::$instances[$id];
    }
    
    /**
     * Destroy all instances (for unit tests)
     * 
     * @return void
     */
    public static function destroyInstances() {
        if (isset(self::$instances)) {
            self::$instances = null;
        }
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
     * @return void
     */
    private function loadInfo()
    {
        if(empty($this->getProperties())) { // haven't been loaded yet
            Exchange::getInstance()->setSecurityGroups();
        }
    }
    
    /**
     * Set the collections
     *
     * @throws Exception\DataException when the list is empty
     * @return void
     */
    public function setCollections()
    {
        $collections = Client::getInstance()->getSecurityGroupCollections($this->getId());
        
        if (empty($collections['collections'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        foreach ($collections['collections'] as $v) {
            $collection = Collection::getInstance($v['name'], $this->getId());
            $collection->setProperty('title', $v['title']);
            
            $this->collections[] = $collection;
        }
    }
    
    /**
     * Get the collections
     *
     * @return array
     */
    public function getCollections()
    {
        if (empty($this->collections)) {
            $this->setCollections();
        }
        
        return $this->collections;
    }
    
    
    /**
     * Get the specific collection
     *
     * @param  string $part_id
     * @return \Panychek\MoEx\Collection
     */
    public function getCollection(string $part_id)
    {
        $collection_id = sprintf('%s_%s', $this->getId(), $part_id);
        $collection = Collection::getInstance($collection_id, $this->getId());
        
        return $collection;
    }
}
