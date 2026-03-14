<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayRegistry
{
    /**
     * @var array<string, PaymentGateway>
     */
    private array $gateways;

    /**
     * Inicializa o registro de gateways disponíveis no sistema
     *
     * @param GatewayOneClient $gatewayOne Cliente de integração do Gateway 1
     * @param GatewayTwoClient $gatewayTwo Cliente de integração do Gateway 2
     * @return void
     */
    public function __construct(GatewayOneClient $gatewayOne, GatewayTwoClient $gatewayTwo)
    {
        $this->gateways = [
            $gatewayOne->driver() => $gatewayOne,
            $gatewayTwo->driver() => $gatewayTwo,
        ];
    }

    /**
     * Resolve e retorna o gateway de pagamento com base no driver informado
     *
     * @param string $driver Identificador do driver do gateway
     * @return PaymentGateway Implementação do gateway correspondente ao driver
     */
    public function resolve(string $driver): PaymentGateway
    {
        if (! array_key_exists($driver, $this->gateways)) {
            throw new InvalidArgumentException("Gateway driver inválido: {$driver}");
        }

        return $this->gateways[$driver];
    }
}
