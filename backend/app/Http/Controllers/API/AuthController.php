<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    //
    public function register(RegisterRequest $request)
    {
        $payload = $request->validated();
        try {
            $user = User::create($payload);
            return response()->json(['message' => 'User registered successfully'], 201);
        } catch (\Exception $error) {
            Log::info("Registration failed =>" . $error->getMessage());
            return response()->json(['message' => 'Failed to register user'], 500);
        }
    }

    public function login(Request $request)
    {
        $payload = $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        try {
            $user = User::where("email", $payload["email"])->first();
            if ($user) {
                if (!Hash::check($payload["password"], $user->password)) {
                    return response()->json(["status" => 401, "message" => "Invalid credentials."]);
                }

                $token = $user->createToken("web")->plainTextToken;
                $authRes = array_merge($user->toArray(), ["token" => $token]);
                return ["status" => 200, "user" => $authRes, "message" => "Loggedin succssfully!"];
            }
            return response()->json(["status" => 401, "message" => "No account found with these credentials."]);
        } catch (\Exception $err) {
            Log::info("user_register_err =>" . $err->getMessage());
            return response()->json(["status" => 500, "message" => "Something went wrong!"], 500);
        }
    }

    // * check credentials
    public function checkCredentias(Request $request)
    {
        $payload = $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        try {
            $user = User::where("email", $payload["email"])->first();
            if ($user) {
                if (!Hash::check($payload["password"], $user->password)) {
                    return response()->json(["status" => 401, "message" => "Invalid credentials."]);
                }
                return ["status" => 200, "message" => "Loggedin succssfully!"];
            }
            return response()->json(["status" => 401, "message" => "No account found with these credentials."]);
        } catch (\Exception $err) {
            Log::info("user_register_err =>" . $err->getMessage());
            return response()->json(["status" => 500, "message" => "Something went wrong!"], 500);
        }
    }

    // * Logout
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ["status" => 200, "message" => "logged out successfully!"];
        } catch (\Exception $err) {
            Log::info("user_logout_err =>" . $err->getMessage());
            return response()->json(["status" => 500, "message" => "Something went wrong!"], 500);
        }
    }
}
