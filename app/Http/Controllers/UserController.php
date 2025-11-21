<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('profile_img')) {
            $path = $request->file('profile_img')->store('profiles', 'public');
            $validatedData['profile_img'] = $path;
        }

        if ($request->hasFile('id_img')) {
            $path = $request->file('id_img')->store('id_pictures', 'public');
            $validatedData['id_img'] = $path;
        }

        $user = User::create([
            'first_name'    => $validatedData['first_name'],
            'last_name'     => $validatedData['last_name'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'profile_img'   => $validatedData['profile_img'] ?? null,
            'id_img'        => $validatedData['id_img'] ?? null,
            'phone_number'  => $validatedData['phone_number'],
            'role'          => $validatedData['role'],
        ]);


        return response()->json([
            'message' => 'user created successfully',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|regex:/^[0-9]+$/|min:8|max:20'
        ]);


        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Phone number is not registered'
            ], 404);
        }

        $token = $user->createToken('Auth_Token')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'User logged out successfully'
        ], 200);
    }
}
