<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Lista os produtos paginados em ordem crescente de identificador
     *
     * @return JsonResponse Resposta com a paginação de produtos
     */
    public function index(): JsonResponse
    {
        return response()->json(Product::query()->orderBy('id')->paginate(15));
    }

    /**
     * Cria um novo produto com os dados informados na requisição
     *
     * @param Request $request Requisição contendo nome e valor do produto
     * @return JsonResponse Resposta com o produto criado
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::query()->create($validated);

        return response()->json($product, 201);
    }

    /**
     * Exibe os dados de um produto específico
     *
     * @param Product $product Produto resolvido por model binding
     * @return JsonResponse Resposta com os dados do produto
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Atualiza parcialmente os dados de um produto existente
     *
     * @param Request $request Requisição com os campos a serem atualizados
     * @param Product $product Produto que será atualizado
     * @return JsonResponse Resposta com os dados atualizados do produto
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'integer', 'min:1'],
        ]);

        $product->update($validated);

        return response()->json($product->fresh());
    }

    /**
     * Remove um produto do sistema
     *
     * @param Product $product Produto a ser removido
     * @return JsonResponse Resposta sem conteúdo após a exclusão
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(status: 204);
    }
}
