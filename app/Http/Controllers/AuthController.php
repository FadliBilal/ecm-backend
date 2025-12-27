<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/register",
     * tags={"Authentication"},
     * summary="Register User Baru",
     * description="User wajib mengirimkan location_id yang didapat dari endpoint /locations",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name","email","password","role","location_id","location_label"},
     * @OA\Property(property="name", type="string", example="Budi Santoso"),
     * @OA\Property(property="email", type="string", format="email", example="budi@test.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123"),
     * @OA\Property(property="role", type="string", enum={"buyer", "seller"}, example="buyer"),
     * @OA\Property(property="location_id", type="integer", example=69335, description="ID Lokasi dari RajaOngkir/Komerce"),
     * @OA\Property(property="location_label", type="string", example="TAMBAKSARI, SURABAYA, JAWA TIMUR", description="Label lokasi lengkap"),
     * @OA\Property(property="full_address", type="string", example="Jl. Kenari No. 5, RT 01 RW 02", description="Alamat detail (Jalan/Nomor Rumah)")
     * )
     * ),
     * @OA\Response(response=201, description="Register Berhasil")
     * )
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:buyer,seller',
            'location_id' => 'required|integer', // Wajib Integer
            'location_label' => 'required|string',
            'full_address' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'location_id' => $validated['location_id'],
            'location_label' => $validated['location_label'],
            'full_address' => $validated['full_address'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Register success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/login",
     * tags={"Authentication"},
     * summary="Login User",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email","password"},
     * @OA\Property(property="email", type="string", format="email", example="buyer@test.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Login Berhasil",
     * @OA\JsonContent(
     * @OA\Property(property="access_token", type="string", example="1|AbCdEfGh..."),
     * @OA\Property(property="token_type", type="string", example="Bearer"),
     * @OA\Property(property="data", type="object", description="Data User")
     * )
     * ),
     * @OA\Response(response=401, description="Email/Password Salah")
     * )
     */
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
            'location_id' => 'required|integer',
            'location_label' => 'required|string',
            'full_address' => 'required|string',
        ]);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'location_id' => $request->location_id,
            'location_label' => $request->location_label,
            'full_address' => $request->full_address,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * @OA\Post(
     * path="/logout",
     * tags={"Authentication"},
     * summary="Logout User",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Logout Berhasil")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }
}