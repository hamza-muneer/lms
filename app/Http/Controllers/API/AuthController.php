<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
    public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $otp = rand(100000, 999999); // 6-digit OTP

    // Delete old OTPs
    DB::table('password_otps')->where('email', $request->email)->delete();

    DB::table('password_otps')->insert([
        'email' => $request->email,
        'otp' => $otp,
        'expires_at' => Carbon::now()->addMinutes(15),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Send OTP email (LOG for now)
    Mail::raw("Your password reset OTP is: $otp (valid for 15 minutes)", function ($message) use ($request) {
        $message->to($request->email)
                ->subject('Password Reset OTP');
    });

    return response()->json([
        'status' => true,
        'message' => 'OTP sent to your email'
    ]);
}
public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'otp' => 'required|digits:6',
        'password' => 'required|min:8|confirmed',
    ]);

    $record = DB::table('password_otps')
        ->where('email', $request->email)
        ->where('otp', $request->otp)
        ->first();

    if (!$record) {
        return response()->json([
            'message' => 'Invalid OTP'
        ], 400);
    }

    if (Carbon::now()->greaterThan($record->expires_at)) {
        return response()->json([
            'message' => 'OTP expired'
        ], 400);
    }

    // Reset password
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->save();

    // Delete OTP after use
    DB::table('password_otps')->where('email', $request->email)->delete();

    return response()->json([
        'message' => 'Password reset successful'
    ], 200);
}


}
