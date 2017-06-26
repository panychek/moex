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
     * @param  string $value
     * @return void
     */
    protected function setProperty(string $name, string $value)
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
     * Set the language
     *
     * @param  string $lang
     * @return void
     */
    public function setLanguage($lang)
    {
        Client::getInstance()->setLanguage($lang);
    }
    
    /**
     * Get the language
     *
     * @return string
     */
    public function getLanguage()
    {
        return Client::getInstance()->getLanguage();
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
}
