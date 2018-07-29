<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

abstract class AbstractEntry
{
    /**
     * @var string
     */
    protected $id = '';
    
    /**
     * @var array
     */
    private $properties = array();
    
    /**
     * Date string for the DateTime constructor
     *
     * @var string
     */
    private $current_datetime_str = 'now';
    
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
            if (method_exists($this, 'loadInfo')) {
                $this->loadInfo();
            }
            
            $property = $this->getPropertyFromMethod($name);
            if (isset($this->getProperties()[$property])) {
                return $this->getProperties()[$property];
            }
        }
        
        $message = sprintf('Method "%s" does not exist', $name);
        throw new Exception\BadMethodCallException($message);
    }
    
    /**
     * Set the id
     *
     * @param  string $id
     * @return void
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }
    
    /**
     * Get the id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set the properties
     *
     * @param  array $properties
     * @return void
     */
    protected function setProperties(array $properties)
    {
        $properties = array_change_key_case($properties);
        $this->properties = $properties;
    }
    
    /**
     * Get the properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
    
    /**
     * Set the property
     *
     * @param  string $name
     * @param  string|null $value
     * @return void
     */
    public function setProperty(string $name, $value)
    {
        $this->properties[$name] = $value;
    }
    
    /**
     * Get the property
     *
     * @param  string $name
     * @return mixed
     */
    public function getProperty(string $name)
    {
        return $this->properties[$name];
    }
    
    /**
     * Set the current date and time (for unit tests)
     *
     * @param  string $string
     * @return void
     */
    public function setCurrentDateTime(string $string)
    {
        $this->current_datetime_str = $string;
    }
    
    /**
     * Get the current date and time
     *
     * @return \DateTime
     */
    private function getCurrentDateTime() {
        $timezone = new \DateTimeZone(Client::TIMEZONE);
        $date = new \DateTime($this->current_datetime_str, $timezone);
        
        return $date;
    }    
    
    /**
     * Check if the method is a getter method
     * 
     * @param  string $name
     * @return boolean
     */
    protected function isGetterMethod($name)
    {
        return substr($name, 0, 3) == 'get';
    }
    
    /**
     * Get the property from method name
     *
     * @param  string $name
     * @return string
     */
    public function getPropertyFromMethod(string $name)
    {
        return strtolower(substr($name, 3));
    }
    
    /**
     * Get a date string
     *
     * @param  \DateTime|false $date
     * @return string
     */
    protected function getDateString($date)
    {
        if ($date === false) { // today
            $date = $this->getCurrentDateTime();
        }
        
        return $date->format(Client::DATE_FORMAT);
    }
}
