<?php
namespace MonetaPayments;

/**
 * Validation Exception
 */
class TurnkeyTokenException extends \Exception{
    protected $message = 'A token error occurred';
    protected $code = '-2000';
}