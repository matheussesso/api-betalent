<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Inicializa o controller com o serviço responsável pelo fluxo de pagamento
     *
     * @param PaymentService $paymentService Serviço de pagamentos utilizado para processar compras
     * @return void
     */
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * Processa uma nova compra com cliente, cartão e itens informados
     *
     * @param Request $request Requisição com os dados necessários para a compra
     * @return JsonResponse Resposta com o resultado da compra e a transação gerada
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'card_number' => ['required', 'digits:16'],
            'cvv' => ['required', 'digits:3'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $transaction = $this->paymentService->purchase($validated);
        } catch (PaymentFailedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ], 422);
        }

        return response()->json([
            'message' => 'Compra realizada com sucesso.',
            'data' => $transaction,
        ], 201);
    }
}
