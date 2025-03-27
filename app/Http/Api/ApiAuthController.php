<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Define o tempo de expiração do token para 5 minutos
        $expiresAt = now()->addMinutes(config('sanctum.token_expiration', 5));

        // Revoga tokens anteriores
        $user->tokens()->delete();

        $token = $user->createToken(
            $request->device_name ?? $request->email,
            ['*'],
            $expiresAt
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // Extrair o ID do usuário do token
        $tokenParts = explode('|', $request->token);

        if (count($tokenParts) !== 2) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        $tokenId = explode('_', $tokenParts[0]);

        if (count($tokenId) !== 2) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        $userId = $tokenId[0];

        // Buscar o usuário
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 401);
        }

        // Revogar o token atual
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);

        if ($personalAccessToken) {
            $personalAccessToken->delete();
        } else {
            // Se não encontrar o token diretamente, revogar todos os tokens do usuário
            $user->tokens()->delete();
        }

        // Criar um novo token
        $expiresAt = now()->addMinutes(config('sanctum.token_expiration', 5));
        $token = $user->createToken('api_token', ['*'], $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
