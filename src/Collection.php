<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

class Collection extends AbstractEntry
{
    /**
     * @var array
     */
    private static $instances = array();
    
    /**
     * The security group the collection belongs to
     *
     * @var \Panychek\MoEx\SecurityGroup
     */
    private $security_group = null;
    
    /**
     * @var array
     */
    private $securities = array();
    
    /**
     * Constructor
     * 
     * @param  string $id
     * @param  string $security_group_id
     * @throws Exception\InvalidArgumentException for invalid collection ids
     * @return void
     */
    public function __construct(string $id, string $security_group_id)
    {
        if ($id === '') {
            $message = 'Invalid collection id';
            throw new Exception\InvalidArgumentException($message);
        }
        
        $this->setId($id);
        $this->setSecurityGroup($security_group_id);
    }
    
    /**
     * Get an instance
     *
     * @param  string $id
     * @param  string $security_group_id
     * @return \Panychek\MoEx\Collection
     */
    public static function getInstance(string $id, string $security_group_id)
    {
        if (!isset(self::$instances[$security_group_id][$id])) {
            self::$instances[$security_group_id][$id] = new self($id, $security_group_id);
        }
        
        return self::$instances[$security_group_id][$id];
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
            $security_group = $this->getSecurityGroup();
            
            $collection = Client::getInstance()->getCollection($security_group->getId(), $this->getId());
            
            if (empty($collection['collections'])) {
                $message = sprintf('Collection "%s" not found', $this->getId());
                throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
            }
            
            $properties = $collection['collections'][0];
            $this->setProperties($properties);
        }
    }
    
    /**
     * Set the security group
     *
     * @param  string $security_group_id
     * @return void
     */
    public function setSecurityGroup(string $security_group_id)
    {
        $this->security_group = SecurityGroup::getInstance($security_group_id);
    }
    
    /**
     * Get the security group
     *
     * @return \Panychek\MoEx\SecurityGroup
     */
    public function getSecurityGroup()
    {
        return $this->security_group;
    }
    
    /**
     * Set the securities
     *
     * @throws Exception\DataException when the list is empty
     * @return void
     */
    public function setSecurities()
    {
        $securities = Client::getInstance()->getCollectionSecurities($this->getSecurityGroup()->getId(), $this->getId());
        
        if (empty($securities['securities'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        foreach ($securities['securities'] as $v) {
            $security = new Security('#' . $v['SECID']);
            //$security->setProperties($v);
            
            $this->securities[] = $security;
        }
    }
    
    /**
     * Get the securities
     *
     * @return array
     */
    public function getSecurities()
    {
        if (empty($this->securities)) {
            $this->setSecurities();
        }
        
        return $this->securities;
    }
}
