<?php

namespace App\Http\Controllers;

use App\Models\User; // Fix typo in the model namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Correct capitalization
use Illuminate\Support\Facades\Validator; // Correct capitalization
use Tymon\JWTAuth\Facades\JWTAuth; // Correct namespace
use Tymon\JWTAuth\Exceptions\JWTException; // Correct namespace
use Tymon\JWTAuth\Exceptions\TokenExpiredException; // Correct class name
use Tymon\JWTAuth\Exceptions\TokenInvalidException; // Correct class name
use Illuminate\Support\Facades\Response;
use App\Models\Pelanggan;



class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('kode', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user = User::where('kode',$request->kode)->orWhere('nm_petugas', $request->nm_petugas)->first();

        if ($user) {
            return response()->json([
                'data' => [
                    'kode' => $user->kode,
                    'nm_petugas' => $user->nm_petugas,
                    'role' => $user->role,
                    'cabang' => $user->cabang,
                    'petugas' => $user->kode,
                    'token' => $token
                ]
                ], 200);
        } else {
            return response()->json([
                'error' => 'user_not_found'
            ], 404);
        }
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|max:10',
            'nm_petugas' => 'required|string|max:255',
            'password' => 'required|string|min:3|confirmed',
            'cabang' => 'required|string|max:2',    
            'role' => 'required|string|max:2',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $user = User::create([
            'kode' => $request->get('kode'),
            'nm_petugas' => $request->get('nm_petugas'),
            'password' => Hash::make($request->input('password')),
            'cabang' => $request->get('cabang'),
            'role' => $request->get('role'),
        ]);
        $token = JWTAuth::fromUser($user);
        return response()->json(compact('user', 'token'), 201);
    }

    public function getAuthenticatedUser()
{
    try {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['user_not_found'], 404);
        }

        return response()->json(compact('user'));
    } catch (TokenExpiredException $e) {
        return response()->json(['token_expired'], $e->getCode());
    } catch (TokenInvalidException $e) {
        return response()->json(['token_invalid'], $e->getCode()); 
    } catch (JWTException $e) {
        return response()->json(['token_absent'], $e->getCode()); 
    }
}

    public function logout(Request $request)
    {        
        //remove token
        $removeToken = JWTAuth::invalidate(JWTAuth::getToken());

        if($removeToken) {
            //return response JSON
            return response()->json([
                'success' => true,
                'message' => 'Logout Berhasil!',  
            ]);
        }
    }


    public function getUser()
    {
        try {
            // Mendapatkan user yang sedang diautentikasi berdasarkan token
            $user = JWTAuth::parseToken()->authenticate();
            
            // Pastikan user ditemukan
            if (!$user) {
                return Response::json(['error' => 'user_not_found'], 404);
            }
    
            // Return the user data
            return Response::json($user);
        } catch (JWTException $e) {
            // Tangani error JWT
            return response()->json(['token_invalid'], 401);
        }
    }

}
