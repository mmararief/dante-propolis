<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Auth"},
     *     summary="Registrasi akun baru",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_lengkap","username","email","password"},
     *             @OA\Property(property="nama_lengkap", type="string", example="Jane Doe"),
     *             @OA\Property(property="username", type="string", example="jane"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="no_hp", type="string", nullable=true),
     *             @OA\Property(property="role", type="string", enum={"pelanggan","reseller"}, example="pelanggan")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Registrasi berhasil"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'in:pelanggan,reseller'],
        ]);

        $user = User::create([
            'nama_lengkap' => $data['nama_lengkap'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'no_hp' => $data['no_hp'] ?? null,
            'role' => $data['role'] ?? 'pelanggan',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user,
        ], 'Registrasi berhasil', 201);
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Auth"},
     *     summary="Login dan mendapatkan token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login berhasil"),
     *     @OA\Response(response=401, description="Kredensial salah")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->fail('Email atau password salah', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user,
        ], 'Login berhasil');
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Auth"},
     *     summary="Logout dan revoke token",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Logout berhasil")
     * )
     */
    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return $this->success(null, 'Logout berhasil');
    }
}
