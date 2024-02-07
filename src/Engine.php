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
    use ValidationTrait;
    
    /**
     * @var array
     */
    private static $instances = array();
    
    /**
     * @var array
     */
    private $markets = array();
    
    /**
     * @var array
     */
    private $turnovers = array();
    
    /**
     * @var array
     */
    private $num_trades = array();
    
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
     * Load the info
     *
     * @throws Exception\DataException for unknown engines
     * @return void
     */
    protected function loadInfo()
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
     * Set the markets
     *
     * @throws Exception\DataException when the list is empty
     * @return void
     */
    public function setMarkets()
    {
        $markets = Client::getInstance()->getMarketList($this->getId());
        
        if (empty($markets['markets'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        foreach ($markets['markets'] as $v) {
            $market = Market::getInstance($v['NAME'], $this->getId());
            $market->setProperty('title', $v['title']);
            
            $this->markets[] = $market;
        }
    }
    
    /**
     * Get the markets
     *
     * @return array
     */
    public function getMarkets()
    {
        if (empty($this->markets)) {
            $this->setMarkets();
        }
        
        return $this->markets;
    }
    
    /**
     * Set the turnovers
     *
     * @param  string $date
     * @param  array $turnovers
     * @return void
     */
    public function setTurnovers(string $date, array $turnovers)
    {
        $this->turnovers[$date] = $turnovers;
    }
    
    /**
     * Get the turnovers
     *
     * @param  string $currency
     * @param  \DateTime|string|false $date
     * @return float|null
     */
    public function getTurnovers(string $currency = 'rub', $date = false)
    {
        $currency = strtolower($currency);
        $this->validateCurrency($currency);
        
        $this->validateDate($date);
        
        if (is_string($date)) {
            $timezone = new \DateTimeZone(Client::TIMEZONE);
            $date = new \DateTime($date, $timezone);
        }
        
        $date_str = $this->getDateString($date);
        
        if (empty($this->turnovers[$date_str])) {
            Exchange::getInstance()->setTurnovers($date);
        }
        
        if ($date === false) {
            $non_empty_turnovers = array_filter($this->turnovers);
            $last_day_turnovers = end($non_empty_turnovers);
            if ($last_day_turnovers) {
                return $last_day_turnovers[$currency];
            }
            return 0.0;
        } else {
            return $this->turnovers[$date_str][$currency];
        }
    }
    
    /**
     * Set the number of trades
     *
     * @param  string $date
     * @param  int|null $number
     * @return void
     */
    public function setNumberOfTrades(string $date, $number)
    {
        $this->num_trades[$date] = $number;
    }
    
    /**
     * Get the number of trades
     *
     * @param  \DateTime|string|false $date
     * @return int|null
     */
    public function getNumberOfTrades($date = false)
    {
        $this->validateDate($date);
        
        if (is_string($date)) {
            $timezone = new \DateTimeZone(Client::TIMEZONE);
            $date = new \DateTime($date, $timezone);
        }
        
        $date_str = $this->getDateString($date);
        
        if (!array_key_exists($date_str, $this->num_trades)) {
            Exchange::getInstance()->setTurnovers($date);
        }
        
        if ($date === false) {
            $non_empty_num_trades = array_filter($this->num_trades);
            $last_day_num_trades = end($non_empty_num_trades);
            
            return $last_day_num_trades;
            
        } else {
            return $this->num_trades[$date_str];
        }
    }
}
