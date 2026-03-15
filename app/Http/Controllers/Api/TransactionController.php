<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GatewayRequestException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * Inicializa o controller com o serviço de pagamentos para operações de transação
     *
     * @param PaymentService $paymentService Serviço responsável por operações de pagamento e reembolso
     * @return void
     */
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * Lista as transações paginadas com cliente, gateway e produtos relacionados
     *
     * @return JsonResponse Resposta com a paginação das transações
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Transaction::query()
                ->with(['client', 'gateway', 'products'])
                ->orderByDesc('id')
                ->paginate(15)
        );
    }

    /**
     * Exibe os detalhes de uma transação específica
     *
     * @param Transaction $transaction Transação resolvida por model binding
     * @return JsonResponse Resposta com os dados completos da transação
     */
    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json($transaction->load(['client', 'gateway', 'products']));
    }

    /**
     * Solicita o reembolso de uma transação já existente
     *
     * @param Transaction $transaction Transação que será reembolsada
     * @return JsonResponse Resposta com o resultado do reembolso
     */
    public function refund(Transaction $transaction): JsonResponse
    {
        try {
            $transaction = $this->paymentService->refund($transaction);
        } catch (PaymentFailedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (GatewayRequestException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Reembolso realizado com sucesso.',
            'data' => $transaction,
        ]);
    }
}
