<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\GatewayRequestException;
use Illuminate\Support\Facades\Http;

class GatewayOneClient implements PaymentGateway
{
    /**
     * Retorna o identificador do driver deste gateway
     *
     * @return string Nome do driver utilizado para resolver o gateway
     */
    public function driver(): string
    {
        return 'gateway_1';
    }

    /**
     * Envia uma solicitação de cobrança para o Gateway 1
     *
     * @param array $payload Dados da cobrança, incluindo valor, cliente e cartão
     * @return array Resposta normalizada contendo o identificador externo e status da transação
     */
    public function charge(array $payload): array
    {
        $token = $this->authenticate();

        $response = Http::baseUrl(config('services.gateway_1.base_url'))
            ->acceptJson()
            ->timeout(8)
            ->withToken($token)
            ->post('/transactions', [
                'amount' => $payload['amount'],
                'name' => $payload['name'],
                'email' => $payload['email'],
                'cardNumber' => $payload['card_number'],
                'cvv' => $payload['cvv'],
            ]);

        if ($response->failed()) {
            throw new GatewayRequestException('Falha no Gateway 1.');
        }

        $data = $response->json() ?? [];

        return [
            'external_id' => (string) ($data['id'] ?? $data['external_id'] ?? ''),
            'status' => (string) ($data['status'] ?? 'approved'),
        ];
    }

    /**
     * Solicita o reembolso de uma transação no Gateway 1
     *
     * @param string $externalId Identificador externo da transação no gateway
     * @return array Resposta normalizada contendo o status do reembolso
     */
    public function refund(string $externalId): array
    {
        $token = $this->authenticate();

        $response = Http::baseUrl(config('services.gateway_1.base_url'))
            ->acceptJson()
            ->timeout(8)
            ->withToken($token)
            ->post("/transactions/{$externalId}/charge_back");

        if ($response->failed()) {
            throw new GatewayRequestException('Falha ao reembolsar no Gateway 1.');
        }

        $data = $response->json() ?? [];

        return [
            'status' => (string) ($data['status'] ?? 'refunded'),
        ];
    }

    /**
     * Realiza autenticação no Gateway 1 para obtenção de token de acesso
     *
     * @return string Token de autenticação retornado pelo gateway ou token de fallback da configuração
     */
    private function authenticate(): string
    {
        $response = Http::baseUrl(config('services.gateway_1.base_url'))
            ->acceptJson()
            ->timeout(8)
            ->post('/login', [
                'email' => config('services.gateway_1.email'),
                'token' => config('services.gateway_1.token'),
            ]);

        if ($response->failed()) {
            throw new GatewayRequestException('Falha na autenticação do Gateway 1.');
        }

        $data = $response->json() ?? [];

        return (string) ($data['token'] ?? config('services.gateway_1.token'));
    }
}
