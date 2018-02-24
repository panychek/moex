<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

/**
 * Top of the hierarchy
 */
class Exchange extends AbstractEntry
{
    /**
     * @var \Panychek\MoEx\Exchange
     */
    private static $instance = null;
    
    /**
     * @var array
     */
    private $engines = array();
    
    /**
     * @var array
     */
    private $security_groups = array();
    
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
     * @return \Panychek\MoEx\Exchange
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * Destroy an instance (for unit tests)
     * 
     * @return void
     */
    public static function destroyInstance() {
        if (isset(self::$instance)) {
            self::$instance = null;
        }
    }
    
    /**
     * Destroy all created instances (for unit tests)
     * 
     * @return void
     */
    public static function destroy() {
        self::destroyInstance();
        Engine::destroyInstances();
        Market::destroyInstances();
        SecurityGroup::destroyInstances();
        Collection::destroyInstances();
    }
    
    
    /**
     * Wrapper for the Client::authenticate() method
     * 
     * @param  string $username
     * @param  array  $password
     * @return true
     */
    public static function authenticate(string $username, string $password) {
        return Client::getInstance()->authenticate($username, $password);
    }
    
    /**
     * Set the engines
     *
     * @throws Exception\DataException when the list is empty
     * @return void
     */
    private function setEngines()
    {
        $engines = Client::getInstance()->getEngineList();
        
        if (empty($engines['engines'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        foreach ($engines['engines'] as $v) {
            $engine = Engine::getInstance($v['name']);
            $engine->setProperty('title', $v['title']);
            
            $this->engines[] = $engine;
        }
    }
        
    /**
     * Get the engines
     *
     * @return array
     */
    public function getEngines()
    {
        if (empty($this->engines)) {
            $this->setEngines();
        }
        
        return $this->engines;
    }
    
    /**
     * Set the security groups
     *
     * @throws Exception\DataException when the list is empty
     * @return void
     */
    public function setSecurityGroups()
    {
        $security_groups = Client::getInstance()->getSecurityGroups();
        
        if (empty($security_groups['securitygroups'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        foreach ($security_groups['securitygroups'] as $v) {
            $security_group = SecurityGroup::getInstance($v['name']);
            $security_group->setProperty('title', $v['title']);
            
            $this->security_groups[] = $security_group;
        }
    }
        
    /**
     * Get the security groups
     *
     * @return array
     */
    public function getSecurityGroups()
    {
        if (empty($this->security_groups)) {
            $this->setSecurityGroups();
        }
        
        return $this->security_groups;
    }
    
    /**
     * Set the turnovers
     *
     * @param  \DateTime|false $date
     * @throws Exception\DataException when the list is empty
     * @return void
     */
    public function setTurnovers($date)
    {
        $turnovers = Client::getInstance()->getTurnovers($date);
        
        if (empty($turnovers['turnoversprevdate'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        $date_str = $this->getDateString($date);
        if (!isset($this->turnovers[$date_str])) {
            $this->turnovers[$date_str] = null;
            $this->num_trades[$date_str] = null;
        }
        
        $keys = array('turnovers', 'turnoversprevdate');
        foreach ($keys as $key) {
            if (!empty($turnovers[$key])) {
                foreach ($turnovers[$key] as $v) {
                    $date = new \DateTime($v['UPDATETIME']);
                    $date_str = $date->format(Client::DATE_FORMAT);
                    
                    $data = array(
                        'rub' => $v['VALTODAY'],
                        'usd' => $v['VALTODAY_USD']
                    );
                        
                    if ($v['NAME'] == 'TOTALS') { // totals
                        $this->turnovers[$date_str] = $data;
                        $this->num_trades[$date_str] = $v['NUMTRADES'];
                        
                    } else { // specific engine
                        $engine = Engine::getInstance($v['NAME']);
                        
                        $engine->setProperty('title', $v['TITLE']);
                        $engine->setTurnovers($date_str, $data);
                        $engine->setNumberOfTrades($date_str, $v['NUMTRADES']);
                    }
                }
            }
        }
        
        ksort($this->turnovers);
        ksort($this->num_trades);
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
        if (!array_key_exists($date_str, $this->turnovers)) {
            $this->setTurnovers($date);
        }
        
        if ($date === false) {
            $non_empty_turnovers = array_filter($this->turnovers);
            $last_day_turnovers = end($non_empty_turnovers);
            
            return $last_day_turnovers[$currency];
            
        } else {
            return $this->turnovers[$date_str][$currency];
        }
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
            $this->setTurnovers($date);
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
