<?php
namespace MonetaPayments;

/**
 * Communication Exception
 */
class TurnkeyCommunicationException extends \Exception{
    protected $message = 'A communication error occurred';
    protected $code = '-1998';
}