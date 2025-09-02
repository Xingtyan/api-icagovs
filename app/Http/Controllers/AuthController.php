<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Schema;

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
    $data = $request->validate([
        'email'    => 'required|string',   // permite email o "usuario"
        'password' => 'required|string',
    ]);

    // Buscar por email y, si existen, por username/usuario
    $q = User::query()->where('email', $data['email']);
    if (Schema::hasColumn('users', 'username')) {
        $q->orWhere('username', $data['email']);
    }
    if (Schema::hasColumn('users', 'usuario')) {
        $q->orWhere('usuario', $data['email']);
    }
    $user = $q->first();

    if (! $user) return response()->json(['message' => 'Unauthorized'], 401);

    $plain  = $data['password'];
    $hashed = $user->password;
    $ok = false;

    // ¿Parece bcrypt/argon? -> Hash::check
    $looksBcrypt = Str::startsWith($hashed, ['$2y$', '$2a$', '$2b$']);
    $looksArgon  = Str::startsWith($hashed, ['$argon2id$', '$argon2i$']);
    try {
        if ($looksBcrypt || $looksArgon) {
            $ok = Hash::check($plain, $hashed);
        }
    } catch (\Throwable $e) {
        $ok = false;
    }

    // Fallback legacy (MD5 / SHA1 / plano) + migración a bcrypt
    if (! $ok) {
        if (strlen($hashed) === 32 && ctype_xdigit($hashed) && hash_equals($hashed, md5($plain))) {
            $ok = true;
        } elseif (strlen($hashed) === 40 && ctype_xdigit($hashed) && hash_equals($hashed, sha1($plain))) {
            $ok = true;
        } elseif (hash_equals($hashed, $plain)) {
            $ok = true;
        }
        if ($ok) { // migrar a bcrypt
            $user->password = Hash::make($plain);
            $user->save();
        }
    }

    if (! $ok) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Genera JWT sin attempt()
    $token = JWTAuth::fromUser($user);

    return response()->json([
        'access_token' => $token,
        'token_type'   => 'bearer',
        'expires_in'   => config('jwt.ttl') * 60,
    ]);
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
