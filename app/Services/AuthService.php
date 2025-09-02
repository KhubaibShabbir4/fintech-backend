<?php

namespace App\Services;

use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class AuthService implements AuthServiceInterface
{
    public function register(array $data)
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // ✅ Ensure role exists before assigning
        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])
                ->where('guard_name', 'api')
                ->first();

            if ($role) {
                $user->assignRole($role);
            } else {
                // Optional: throw error or assign default role
                return [
                    'status'  => 'error',
                    'message' => "Role '{$data['role']}' does not exist for guard 'api'."
                ];
            }
        }

        // ✅ Sanctum token
        $token = $user->createToken('api_token')->plainTextToken;

        return [
            'status' => 'success',
            'user'   => $user,
            'token'  => $token
        ];
    }


    public function login(array $credentials)
    {
        if (! $token = Auth::attempt($credentials)) {
            return false;
        }
        return $token;
    }

    public function profile()
    {
        return Auth::user();
    }

    public function logout()
    {
        Auth::logout();
    }
}
