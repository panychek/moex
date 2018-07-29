<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

trait ValidationTrait
{
    /**
     * Make sure a currency code is valid
     *
     * @param  string $currency
     * @throws \Panychek\MoEx\Exception\InvalidArgumentException
     * @return void
     */
    protected function validateCurrency(string $currency, array $allowed_currencies = array('rub', 'usd'))
    {
        if (!in_array($currency, $allowed_currencies)) {
            $message = 'Unsupported currency';
            throw new Exception\InvalidArgumentException($message);
        }
    }
    
    /**
     * Make sure a date is valid
     *
     * @param  \DateTime|string|false $date
     * @throws \Panychek\MoEx\Exception\InvalidArgumentException
     * @return void
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
}
