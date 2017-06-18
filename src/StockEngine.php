<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx;

class StockEngine extends Engine
{
    /**
     * @var string
     */
    protected $id = 'stock';
    
    /**
     * Get the stock market capitalization
     *
     * @throws Exception\DataException for unsupported data formats
     * @return float
     */
    public function getCapitalization()
    {
        $data = $this->client->getCapitalization();
        
        if (empty($data['issuecapitalization'][0]['ISSUECAPITALIZATION'])) {
            $message = 'No available data';
            throw new Exception\DataException($message, Exception\DataException::EMPTY_RESULT);
        }
        
        return $data['issuecapitalization'][0]['ISSUECAPITALIZATION'];
    }
}
