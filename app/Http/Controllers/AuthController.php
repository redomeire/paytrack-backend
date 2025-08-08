<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

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
            'password' => Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
            'password_confirmation' => 'required|same:password',
            'language' => 'in:en,id',
            'currency' => 'in:USD,IDR',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        event(new Registered($user));
        return $this->sendResponse($user, 'User register successfully.');
    }
    public function login(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            return $this->sendError('User already logged in.', _, 401);
        }
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('User Token', ['user:bill:crud', 'user:payment:crud', 'user:notification:r'])->accessToken;
            $success['name'] = $user->name;

            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
        }
    }
    public function verifyEmail(Request $request, $id, $hash)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return $this->sendError('User not found.', null, 404);
            }
            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return $this->sendError('Invalid verification link.', null, 403);
            }
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                $user->is_verified = true;
                $user->save();
            }

            return $this->sendResponse(null, 'Email verified successfully.');
        } catch (\Throwable $th) {
            Log::error("Email verification error: " . $th->getMessage());
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function resendVerificationEmail(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return $this->sendError('User not found.', null, 404);
            }
            if ($user->email_verified_at != null) {
                return $this->sendError('Email already verified.', null, 400);
            }
            $user->sendEmailVerificationNotification();
            return $this->sendResponse(null, 'Verification email sent successfully.');
        } catch (\Throwable $th) {
            Log::error("Resend verification email error: " . $th->getMessage());
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function logout(Request $request)
    {
        $user = $request->user();
        Log::info("user token: " . $user->token()->id);
        try {
            if ($user) {
                $user->token()->revoke();
                return $this->sendResponse(null, 'User logged out successfully.');
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), _, 500);
        }
    }
    public function handleSocialRedirect($provider)
    {
        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Throwable $th) {
            Log::error("Socialite redirect error: " . $th->getMessage());
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function handleSocialAuthorize($provider)
    {
        try {
            $socialiteUser = Socialite::driver($provider)->user();
            $user_from_email = User::where('email', $socialiteUser->email)->first();

            if ($user_from_email) {
                $user_from_email[$provider . '_id'] = $socialiteUser->id;
                $user_from_email[$provider . '_token'] = $socialiteUser->token;
                $user_from_email['avatar_url'] = $socialiteUser->avatar_original;
                $user_from_email['is_verified'] = true;
                $user_from_email['email_verified_at'] = now();
                if (isset($socialiteUser->refreshToken)) {
                    $user_from_email[$provider . '_refresh_token'] = $socialiteUser->refreshToken;
                }
                $user_from_email->save();
            } else {
                $newUser = User::create([
                    $provider . '_id' => $socialiteUser->id,
                    'name' => $socialiteUser->name,
                    'email' => $socialiteUser->email,
                    $provider . '_token' => $socialiteUser->token,
                    $provider . '_refresh_token' => $socialiteUser->refreshToken ?? null,
                    'avatar_url' => $socialiteUser->avatar_original,
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]);
            }

            $authUser = Auth::user();
            $success['token'] = $authUser->createToken('User Token', ['user:bill:crud', 'user:payment:crud', 'user:notification:r'])->accessToken;

            return $this->sendResponse($success, 'User logged in successfully via ' . $provider);
        } catch (\Throwable $th) {
            Log::error("Socialite callback error: " . $th->getMessage());
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::ResetLinkSent) {
                return $this->sendResponse(null, 'Password reset link sent successfully.');
            }
            throw new \Exception('Failed to send password reset link.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function resetPassword(Request $request, string $token)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email',
                'password' => Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
                'password_confirmation' => 'required|same:password',
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), null, 422);
            }

            $status = Password::reset(
                $request->all(),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PasswordReset) {
                return $this->sendResponse(null, 'Password reset successfully.');
            }
            return $this->sendError('Password reset failed.', null, 400);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
}
