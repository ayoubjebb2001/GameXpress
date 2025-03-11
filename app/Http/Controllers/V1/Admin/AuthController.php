<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:users|min:4|max:255',
            'email' => 'required|unique:users|email',
            'password' => 'required|min:8|max:64|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        $token = $user->createToken($request->name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function login(Request $request)
    {
        $request->validate(
            [
                'email' => 'string|required|exists:users',
                'password' => 'string|required'
            ]
        );

        $user = User::where('email','=',$request->email)->first();
        
        if(!$user || !Hash::check($request->password,$user->password) ){
            return [
                'message' => 'the provided credentials are incorrect'
            ];
        }

        $token = $user->createToken($user->name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();
        return [
            'message' => 'logged out'
        ];
    }
}
