<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentGatewayDisabledException extends RuntimeException
{
    public function __construct(string $message = 'Online payment gateway is disabled.')
    {
        parent::__construct($message);
    }
}
