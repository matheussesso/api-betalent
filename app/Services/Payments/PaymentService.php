<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentFailedException;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Inicializa o serviço de pagamentos com o registro de gateways
     *
     * @param PaymentGatewayRegistry $registry Registro responsável por resolver gateways por driver
     * @return void
     */
    public function __construct(private readonly PaymentGatewayRegistry $registry)
    {
    }

    /**
     * Processa uma compra escolhendo o gateway ativo com maior prioridade disponível
     *
     * @param array $payload Dados da compra, cliente, cartão e itens selecionados
     * @return Transaction Transação criada com relacionamentos carregados
     */
    public function purchase(array $payload): Transaction
    {
        $cvv = (string) $payload['cvv'];

        $products = Product::query()
            ->whereIn('id', collect($payload['products'])->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        /**
         * Mapeia os itens recebidos para uma estrutura contendo produto, quantidade e valor unitário
         *
         * @param array $item Item de produto enviado na compra
         * @return array Item normalizado para processamento interno da compra
         */
        $items = collect($payload['products'])->map(function (array $item) use ($products): array {
            $product = $products->get($item['product_id']);

            if (! $product) {
                throw new PaymentFailedException(message: 'Produto informado não existe.');
            }

            return [
                'product' => $product,
                'quantity' => (int) $item['quantity'],
                'unit_amount' => (int) $product->amount,
            ];
        });

        $totalAmount = $items->sum(fn (array $item): int => $item['unit_amount'] * $item['quantity']);

        if ($totalAmount <= 0) {
            throw new PaymentFailedException(message: 'Valor total da compra inválido.');
        }

        $client = Client::query()->updateOrCreate(
            ['email' => $payload['client_email']],
            ['name' => $payload['client_name']]
        );

        $availableGateways = Gateway::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        if ($availableGateways->isEmpty()) {
            throw new PaymentFailedException(message: 'Nenhum gateway ativo disponível.');
        }

        $errors = [];

        foreach ($availableGateways as $gateway) {
            if ($this->isInvalidCvvForGateway($cvv, $gateway->driver)) {
                $errors[] = [
                    'gateway' => $gateway->driver,
                    'error' => 'CVV inválido para este gateway.',
                ];

                continue;
            }

            try {
                $driver = $this->registry->resolve($gateway->driver);
                $response = $driver->charge([
                    'amount' => $totalAmount,
                    'name' => $client->name,
                    'email' => $client->email,
                    'card_number' => $payload['card_number'],
                    'cvv' => $cvv,
                ]);

                if (empty($response['external_id'])) {
                    throw new PaymentFailedException(message: 'Gateway retornou transação sem ID externo.');
                }

                /**
                 * Persiste a transação e os itens comprados no banco
                 *
                 * @return Transaction Transação persistida com cliente, gateway e produtos carregados
                 */
                return DB::transaction(function () use ($items, $client, $gateway, $response, $totalAmount, $payload) {
                    $transaction = Transaction::query()->create([
                        'client_id' => $client->id,
                        'gateway_id' => $gateway->id,
                        'external_id' => $response['external_id'],
                        'status' => $response['status'] ?? 'approved',
                        'amount' => $totalAmount,
                        'card_last_numbers' => substr($payload['card_number'], -4),
                    ]);

                    foreach ($items as $item) {
                        $transaction->products()->attach($item['product']->id, [
                            'quantity' => $item['quantity'],
                            'unit_amount' => $item['unit_amount'],
                        ]);
                    }

                    return $transaction->load(['client', 'gateway', 'products']);
                });
            } catch (\Throwable $exception) {
                $errors[] = [
                    'gateway' => $gateway->driver,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        throw new PaymentFailedException(errors: $errors);
    }

    /**
     * Verifica se o CVV deve ser rejeitado para um gateway específico.
     *
     * @param string $cvv CVV informado na compra.
     * @param string $driver Identificador do gateway.
     * @return bool True quando o CVV é inválido para o gateway informado.
     */
    private function isInvalidCvvForGateway(string $cvv, string $driver): bool
    {
        return match ($driver) {
            'gateway_1' => in_array($cvv, ['100', '200'], true),
            'gateway_2' => in_array($cvv, ['200', '300'], true),
            default => false,
        };
    }

    /**
     * Solicita reembolso ao gateway da transação e atualiza seu status local
     *
     * @param Transaction $transaction Transação que será reembolsada
     * @return Transaction Transação atualizada após o reembolso
     */
    public function refund(Transaction $transaction): Transaction
    {
        if ($this->isRefundedStatus((string) $transaction->status)) {
            throw new PaymentFailedException(message: 'Transação já foi reembolsada.');
        }

        $transaction->load('gateway');

        $driver = $this->registry->resolve($transaction->gateway->driver);
        $response = $driver->refund($transaction->external_id);

        $transaction->update([
            'status' => $response['status'] ?? 'refunded',
        ]);

        return $transaction->fresh(['client', 'gateway', 'products']);
    }

    /**
     * Verifica se o status representa uma transação já reembolsada.
     *
     * @param string $status Status atual da transação.
     * @return bool True quando o status já representa reembolso realizado.
     */
    private function isRefundedStatus(string $status): bool
    {
        $normalizedStatus = strtolower(trim($status));

        return in_array($normalizedStatus, ['refunded', 'charged_back', 'charge_back'], true);
    }
}
