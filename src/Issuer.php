<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

class Issuer extends AbstractEntry
{
    /**
     * @var array
     */
    private static $instances = array();
    
    /**
     * Constructor
     * 
     * @param  string $id Issuer ID
     * @throws Exception\InvalidArgumentException for invalid issuer ids
     * @return void
     */
    public function __construct(string $id)
    {
        if ($id === '') {
            $message = 'Invalid issuer id';
            throw new Exception\InvalidArgumentException($message);
        }
        
        $this->setId($id);
    }
    
    /**
     * Get an instance
     *
     * @param  string $id
     * @return \Panychek\MoEx\Issuer
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
}
