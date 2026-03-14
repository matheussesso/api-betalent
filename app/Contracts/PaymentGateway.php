<?php

namespace App\Contracts;

interface PaymentGateway
{
    public function driver(): string;

    public function charge(array $payload): array;

    public function refund(string $externalId): array;
}
