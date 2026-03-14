<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    /**
     * Lista todos os gateways ordenados por prioridade.
     *
     * @return JsonResponse Resposta com os gateways cadastrados
     */
    public function index(): JsonResponse
    {
        return response()->json(Gateway::query()->orderBy('priority')->get());
    }

    /**
     * Ativa ou desativa um gateway
     *
     * @param Request $request Requisição contendo o status de ativação
     * @param Gateway $gateway Gateway a ser atualizado
     * @return JsonResponse Resposta com os dados atualizados do gateway
     */
    public function toggle(Request $request, Gateway $gateway): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $gateway->update([
            'is_active' => $validated['is_active'],
        ]);

        return response()->json($gateway->fresh());
    }

    /**
     * Atualiza a prioridade de processamento de um gateway
     *
     * @param Request $request Requisição contendo a nova prioridade
     * @param Gateway $gateway Gateway que terá a prioridade alterada
     * @return JsonResponse Resposta com os dados atualizados do gateway
     */
    public function priority(Request $request, Gateway $gateway): JsonResponse
    {
        $validated = $request->validate([
            'priority' => ['required', 'integer', 'min:1'],
        ]);

        $gateway->update([
            'priority' => $validated['priority'],
        ]);

        return response()->json($gateway->fresh());
    }
}
