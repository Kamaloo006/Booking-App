<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\FirebaseNotificationService;
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
            'password'      => Hash::make($validatedData['password']),
            'profile_img'   => $validatedData['profile_img'] ?? null,
            'id_img'        => $validatedData['id_img'] ?? null,
            'phone_number'  => $validatedData['phone_number'],
            'role'          => $validatedData['role'],
            'status'        => 'pending',
            'fcm_token'     => $request->fcm_token, // هنا يتم استقبال التوكن من الـ Request
        ]);


        return response()->json([
            'message' => 'user created successfully. Please wait for admin approval',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|regex:/^[0-9]+$/|min:8|max:20',
            'password' => 'required|string|min:8|max:255'
        ]);


        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Phone number is not registered'
            ], 404);
        }

        if ($user->status !== 'accepted') {
            return response()->json([
                'message' => 'Your account is not approved yet'
            ], 403);
        }


        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }


        $token = $user->createToken('Auth_Token')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $user,
            'token' => $token
        ], 200);
    }
// لا تنسى استدعاء الـ Service في أعلى الملف


// ... الكود السابق ...

public function testFirebaseConnection()
{
    $fakeToken = "fake-token-123-valid-format-for-testing-purposes";

    try {
        $response = FirebaseNotificationService::sendNotification(
            $fakeToken, 
            "اختبار اتصال", 
            "هل المكتبة تعمل؟"
        );

        return response()->json([
            'status' => 'Backend Setup is Correct!',
            'firebase_response' => $response
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'User logged out successfully'
        ], 200);
    }
    public function show()
    {
        $users = User::all();
        return response()->json([
            'message' => 'Opreration Completed Successfully',
            'User' => $users
        ], 200);
    }
    public function getUserFromToken(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json([
                'message' => 'Operation Completed Successfully',
                'user' => $user
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'User Not Found'
            ], 404);
        }
    }

    public function getAllPendingUsers()
    {
        $users = User::where('status', 'pending')->get();

        return response()->json([
            'message' => 'Pending users retrieved successfully',
            'users' => $users
        ], 200);
    }

    public function approveUser($user_id)
    {
        $user = User::findOrFail($user_id);

        if ($user->status === 'accepted') {
            return response()->json([
                'message' => 'User already approved'
            ], 200);
        }

        $user->status = 'accepted';
        $user->save();

        if ($user->fcm_token) {
        FirebaseNotificationService::sendNotification(
            $user->fcm_token,
            " Your account has been activited",
            "welcome{$user->first_name}، "
        );
    }
    else{
        return response()->json(['message'=>'hello']);
    }

        return response()->json([
            'message' => 'User approved successfully'
        ], 200);
    }



    public function rejectUser($user_id)
    {
        $user = User::findOrFail($user_id);

        if ($user->status !== 'accepted') {
            if ($user->profile_img) {
                Storage::disk('public')->delete($user->profile_img);
            }

            if ($user->id_img) {
                Storage::disk('public')->delete($user->id_img);
            }

            $user->delete();

            return response()->json([
                'message' => 'User rejected By admin. please try again using signUp'
            ], 200);
        } else {
            return response()->json(['message' => 'you can`t reject Accepted User'], 403);
        }
    }
}
