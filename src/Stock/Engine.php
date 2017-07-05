<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx\Stock;

use Panychek\MoEx\Client;
use Panychek\MoEx\Exception\DataException;

class Engine extends \Panychek\MoEx\Engine
{
    /**
     * @var string
     */
    protected $id = 'stock';
    
    /**
     * Get the stock market capitalization
     *
     * @throws \Panychek\MoEx\Exception\DataException for unsupported data formats
     * @return float
     */
    public function getCapitalization()
    {
        $data = Client::getInstance()->getCapitalization();
        
        if (empty($data['issuecapitalization'][0]['ISSUECAPITALIZATION'])) {
            $message = 'No available data';
            throw new DataException($message, DataException::EMPTY_RESULT);
        }
        
        return $data['issuecapitalization'][0]['ISSUECAPITALIZATION'];
    }
}
