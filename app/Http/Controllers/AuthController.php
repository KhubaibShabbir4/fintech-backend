<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegisterRequest;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private AuthServiceInterface $auth) {}

    public function register(AuthRegisterRequest $request): JsonResponse
    {
        return response()->json($this->auth->register($request->validated()), 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'] ?? null)->first();
        if (!$user || !Hash::check($credentials['password'] ?? '', $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
