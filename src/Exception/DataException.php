<?php
/**
 * Moscow Exchange ISS Client
 *
 * @link      http://github.com/panychek/moex
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @author    Igor Panychek panychek@gmail.com
 */

namespace Panychek\MoEx\Exception;

class DataException extends \Exception implements ExceptionInterface
{
    const FAILED_REQUEST = 1;
    const INVALID_RESPONSE = 2;
    const EMPTY_RESULT = 3;
}
