<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * Lista os clientes paginados em ordem crescente de identificador
     *
     * @return JsonResponse Resposta com a paginação de clientes
     */
    public function index(): JsonResponse
    {
        return response()->json(Client::query()->orderBy('id')->paginate(15));
    }

    /**
     * Exibe os detalhes de um cliente com suas transações e produtos relacionados
     *
     * @param Client $client Cliente resolvido por model binding
     * @return JsonResponse Resposta com os dados completos do cliente
     */
    public function show(Client $client): JsonResponse
    {
        return response()->json(
            $client->load([
                'transactions.gateway',
                'transactions.products',
            ])
        );
    }
}
