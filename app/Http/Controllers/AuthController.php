<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Registro de usuario (devuelve token inmediato).
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:8',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Opcional: login automático tras registrar
        $token = auth('api')->attempt([
            'email'    => $data['email'],
            'password' => $request->password, // el original, no el hasheado
        ]);

        return response()->json([
            'message'      => 'User created',
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ], 201);
    }

    /**
     * Login: devuelve JWT.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Usuario autenticado.
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Logout (invalida el token).
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh del token.
     */
    public function refresh()
    {
        $new = JWTAuth::refresh(JWTAuth::getToken());
        return $this->respondWithToken($new);
    }

    /**
     * Respuesta estándar con token.
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60, // minutos → segundos
        ]);
    }
}
