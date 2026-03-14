<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(
        public readonly array $errors = [],
        string $message = 'Nenhum gateway conseguiu processar o pagamento.'
    ) {
        parent::__construct($message);
    }
}
