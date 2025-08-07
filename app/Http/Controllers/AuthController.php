<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $user = $request->user();
        if ($user) {
            return $this->sendError('User already logged in.', _, 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'language' => 'in:en,id',
            'currency' => 'in:USD,IDR',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        return $this->sendResponse($user, 'User register successfully.');
    }
    public function login(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            return $this->sendError('User already logged in.', _, 401);
        }
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('User Token', ['user:bill:crud', 'user:payment:crud', 'user:notification:r'])->accessToken;
            $success['name'] = $user->name;

            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
        }
    }
    public function logout(Request $request)
    {
        $user = $request->user();
        try {
            if ($user) {
                $user->tokens()->revoke();
                return $this->sendResponse(null, 'User logged out successfully.');
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), _, 500);
        }
    }
}
