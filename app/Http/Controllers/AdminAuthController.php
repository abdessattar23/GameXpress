<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6'
            ]);

            if (User::find(1)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Admin already exists'
                ], 400);
            }

            $data['password'] = bcrypt($data['password']);
            User::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Admin created successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function login(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if (!Auth::attempt($data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 400);
            }

            $token = Auth::user()->createToken('authToken');
            $plainTextToken = $token->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'access_token' => $plainTextToken
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function logout(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $request->user()->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logout successful'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
