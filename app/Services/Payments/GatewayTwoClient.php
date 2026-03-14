<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\GatewayRequestException;
use Illuminate\Support\Facades\Http;

class GatewayTwoClient implements PaymentGateway
{
    /**
     * Retorna o identificador do driver deste gateway
     *
     * @return string Nome do driver utilizado para resolver o gateway
     */
    public function driver(): string
    {
        return 'gateway_2';
    }

    /**
     * Envia uma solicitação de cobrança para o Gateway 2
     *
     * @param array $payload Dados da cobrança, incluindo valor, cliente e cartão
     * @return array Resposta normalizada contendo o identificador externo e status da transação
     */
    public function charge(array $payload): array
    {
        $response = Http::baseUrl(config('services.gateway_2.base_url'))
            ->acceptJson()
            ->timeout(8)
            ->withHeaders($this->headers())
            ->post('/transacoes', [
                'valor' => $payload['amount'],
                'nome' => $payload['name'],
                'email' => $payload['email'],
                'numeroCartao' => $payload['card_number'],
                'cvv' => $payload['cvv'],
            ]);

        if ($response->failed()) {
            throw new GatewayRequestException('Falha no Gateway 2.');
        }

        $data = $response->json() ?? [];

        return [
            'external_id' => (string) ($data['id'] ?? $data['external_id'] ?? ''),
            'status' => (string) ($data['status'] ?? 'approved'),
        ];
    }

    /**
     * Solicita o reembolso de uma transação no Gateway 2
     *
     * @param string $externalId Identificador externo da transação no gateway
     * @return array Resposta normalizada contendo o status do reembolso.
     */
    public function refund(string $externalId): array
    {
        $response = Http::baseUrl(config('services.gateway_2.base_url'))
            ->acceptJson()
            ->timeout(8)
            ->withHeaders($this->headers())
            ->post('/transacoes/reembolso', [
                'id' => $externalId,
            ]);

        if ($response->failed()) {
            throw new GatewayRequestException('Falha ao reembolsar no Gateway 2.');
        }

        $data = $response->json() ?? [];

        return [
            'status' => (string) ($data['status'] ?? 'refunded'),
        ];
    }

    /**
     * Monta os cabeçalhos de autenticação exigidos pelo Gateway 2
     *
     * @return array Cabeçalhos com token e segredo de autenticação do gateway
     */
    private function headers(): array
    {
        return [
            'Gateway-Auth-Token' => config('services.gateway_2.auth_token'),
            'Gateway-Auth-Secret' => config('services.gateway_2.auth_secret'),
        ];
    }
}
