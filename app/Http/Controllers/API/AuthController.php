<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    // -------------------------
    // REGISTER
    // -------------------------
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // password_confirmation required
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create token directly (internal)
        // $tokenResult = $user->createToken('Personal Access Token');

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully.',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                // // 'access_token' => $tokenResult->accessToken,
                // 'token_type' => 'Bearer',
                // 'expires_at' => $tokenResult->token->expires_at
            ]
        ], 201);
    }

    // -------------------------
    // LOGIN
    // -------------------------
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $user = Auth::user();
        // $tokenResult = $user->createToken('Personal Access Token');

        return response()->json([
            'status' => true,
            'message' => 'Login successful.',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                // 'access_token' => $tokenResult->accessToken,
                // 'token_type' => 'Bearer',
                // 'expires_at' => $tokenResult->token->expires_at
            ]
        ], 200);
    }

    // -------------------------
    // LOGOUT
    // -------------------------
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete(); // revoke all tokens

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully.'
        ]);
    }

    // -------------------------
    // REFRESH TOKEN
    // -------------------------
    public function refreshToken(Request $request)
    {
        $user = $request->user();

        // Revoke old tokens
        $user->tokens()->delete();

        // Create a new token
        $tokenResult = $user->createToken('Personal Access Token');

        return response()->json([
            'status' => true,
            'message' => 'Token refreshed successfully.',
            'data' => [
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at
            ]
        ], 200);
    }
}
