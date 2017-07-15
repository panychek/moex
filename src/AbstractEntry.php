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
    public function setProperty(string $name, string $value)
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
    
    
    /**
     * Make sure a currency code is valid
     *
     * @param  string $currency
     * @throws \Panychek\MoEx\Exception\InvalidArgumentException
     */
    protected function validateCurrency(string $currency)
    {
        $currencies = array('rub', 'usd');
        
        if (!in_array($currency, $currencies)) {
            $message = 'Unsupported currency';
            throw new Exception\InvalidArgumentException($message);
        }
    }
    
    /**
     * Make sure a date is valid
     *
     * @param  \DateTime|string|false $date
     * @throws \Panychek\MoEx\Exception\InvalidArgumentException
     */
    protected function validateDate($date)
    {
        if (($date instanceof \DateTime) || $date === false) {
            return;
        }
        
        if (is_string($date)) {
            try {
                $timezone = new \DateTimeZone(Client::TIMEZONE);
                $date = new \DateTime($date, $timezone);
                
            } catch (\Exception $e) {
                $message = sprintf('Invalid date passed as string: %s', $date);
                throw new Exception\InvalidArgumentException($message);
            }
            
        } else {
            $message = 'Date must be an instance of \DateTime or a string';
            throw new Exception\InvalidArgumentException($message);
        }
    }
    
    /**
     * Get a date string
     *
     * @param  \DateTime|false $date
     */
    protected function getDateString($date)
    {
        if ($date === false) { // today
            $timezone = new \DateTimeZone(Client::TIMEZONE);
            $date = new \DateTime('now', $timezone);            
        }
        
        return $date->format(Client::DATE_FORMAT);
    }
}
