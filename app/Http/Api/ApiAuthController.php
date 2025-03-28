<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'nullable|string',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'As credenciais fornecidas estão incorretas.'
                ], 401);
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
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'As credenciais fornecidas estão incorretas.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro durante o login: ' . $e->getMessage()
            ], 500);
        }
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

        try {
            // Encontra o token no banco de dados
            $personalAccessToken = PersonalAccessToken::findToken($request->token);

            if (!$personalAccessToken) {
                return response()->json(['error' => 'Token inválido ou expirado'], 401);
            }

            // Obtém o usuário associado ao token
            $user = $personalAccessToken->tokenable;

            if (!$user) {
                return response()->json(['error' => 'Usuário não encontrado'], 401);
            }

            // Obtém as permissões do token antigo
            $abilities = $personalAccessToken->abilities;

            // Revoga o token antigo
            $personalAccessToken->delete();

            // Cria um novo token com as mesmas permissões
            $expiresAt = now()->addMinutes(config('sanctum.token_expiration', 5));
            $token = $user->createToken('api_token', $abilities, $expiresAt);

            return response()->json([
                'token' => $token->plainTextToken,
                'expires_at' => $expiresAt->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao renovar token: ' . $e->getMessage()], 500);
        }
    }
}
