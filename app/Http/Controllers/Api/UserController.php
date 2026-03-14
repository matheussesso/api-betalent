<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Lista os usuários paginados em ordem crescente de identificador
     *
     * @return JsonResponse Resposta com a paginação de usuários
     */
    public function index(): JsonResponse
    {
        return response()->json(User::query()->orderBy('id')->paginate(15));
    }

    /**
     * Cria um novo usuário com os dados validados da requisição
     *
     * @param Request $request Requisição contendo dados de criação do usuário
     * @return JsonResponse Resposta com o usuário criado
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:'.collect(UserRole::cases())->pluck('value')->implode(',')],
        ]);

        $user = User::query()->create($validated);

        return response()->json($user, 201);
    }

    /**
     * Exibe os dados de um usuário específico
     *
     * @param User $user Usuário resolvido por model binding
     * @return JsonResponse Resposta com os dados do usuário
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    /**
     * Atualiza parcialmente os dados de um usuário existente
     *
     * @param Request $request Requisição com os campos a serem atualizados
     * @param User $user Usuário que será atualizado
     * @return JsonResponse Resposta com os dados atualizados do usuário
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'string', 'min:6'],
            'role' => ['sometimes', 'in:'.collect(UserRole::cases())->pluck('value')->implode(',')],
        ]);

        $user->update($validated);

        return response()->json($user->fresh());
    }

    /**
     * Remove um usuário do sistema
     *
     * @param User $user Usuário a ser removido
     * @return JsonResponse Resposta sem conteúdo após a exclusão
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(status: 204);
    }
}
