<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx\Currency;

use Panychek\MoEx\Market;
use Panychek\MoEx\Client;
use Panychek\MoEx\Exception\InvalidArgumentException;

class SeltMarket extends Market
{
    /**
     * @var string
     */
    protected $id = 'selt';
    
    /**
     * @var array
     */
    protected $market_data_mappings = array(
        'lastprice' => 'last',
        'openingprice' => 'open',
        'closingprice' => 'closeprice',
        'dailylow' => 'low',
        'dailyhigh' => 'high'
    );
    
    /**
     * @return callable
     */
    public function getVolumeGetter()
    {
        /**
         * Get the volume
         *
         * @param  array $market_data
         * @param  array $arguments
         * @throws \Panychek\MoEx\Exception\InvalidArgumentException
         * @return mixed
         */
        return function(array $market_data, array $arguments) {
            $currency = (!empty($arguments[0])) ? $arguments[0] : 'rub';
            $currency = strtolower($currency);
            
            $currencies = array('rub', 'usd');
            
            if (!in_array($currency, $currencies)) {
                $message = 'Unsupported currency';
                throw new InvalidArgumentException($message);
            }
            
            $field = 'valtoday';
            if ($currency == 'usd') {
                $field .= '_usd';
            }
            
            return $market_data[$field];
        };
    }
    
    /**
     * @return callable
     */
    public function getChangeGetter()
    {
        /**
         * Get the change
         *
         * @param  array $market_data
         * @param  array $arguments
         * @throws \Panychek\MoEx\Exception\InvalidArgumentException
         * @return mixed
         */
        return function(array $market_data, array $arguments) {
            $range = (!empty($arguments[0])) ? $arguments[0] : 'day';
            $range = strtolower($range);
            
            $measure = (!empty($arguments[1])) ? $arguments[1] : 'currency';
            
            if ($range != 'day') {
                $message = 'Unsupported range. Available ranges: "day"';
                throw new InvalidArgumentException($message);
            }
            
            $field = ($measure == '%') ? 'lasttoprevprice' : 'change';
            
            return $market_data[$field];
        };
    }
    
    /**
     * @return callable
     */
    public function getLastUpdateGetter()
    {
        /**
         * Get the date of the last update
         * 
         * @param  array $market_data
         * @return \DateTime
         */
        return function(array $market_data) {
            $date = explode(' ', $market_data['systime'])[0];
            $time = $market_data['updatetime'];
            $datetime_str = sprintf('%s %s', $date, $time);
            
            $timezone = new \DateTimeZone(Client::TIMEZONE);
            return new \DateTime($datetime_str, $timezone);
        };
    }
}
