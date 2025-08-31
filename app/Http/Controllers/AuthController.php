<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordValidation;

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
            'phone' => 'required|string|max:20',
            'password' => PasswordValidation::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
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
        $foundUser = User::where('email', $request->email)->first();
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), null, 422);
        }
        if (!$foundUser) {
            return $this->sendError('User not found.', null, 404);
        }
        // check if password is null
        if (is_null($foundUser->password)) {
            return $this->sendError('User password is not set. Please login using google or github instead', null, 400);
        }
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $authUser = Auth::user();
            $success['token'] = $authUser->createToken('User Token', ['user:bill:crud', 'user:payment:crud', 'user:notification:r'])->accessToken;
            $success['user'] = $foundUser;

            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Wrong credentials.', null, 400);
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
    public function sendVerificationEmail(Request $request)
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
    public function handleSocialRedirect()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Throwable $th) {
            Log::error("Socialite redirect error: " . $th->getMessage());
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function handleSocialAuthorize(Request $request)
    {
        try {
            $code = $request->query('code');
            if (!$code) {
                return $this->sendError('Code is required', null, 422);
            }
            $socialiteUser = Socialite::driver(driver: 'google')->with(['code' => $code])->stateless()->user();
            if (!$socialiteUser) {
                return $this->sendError('Failed to retrieve user from Google.', null, 500);
            }
            $user_from_email = User::where('email', $socialiteUser->email)->first();
            if ($user_from_email) {
                $user_from_email['google_id'] = $socialiteUser->id;
                $user_from_email['google_token'] = $socialiteUser->token;
                $user_from_email['avatar_url'] = $socialiteUser->avatar_original;
                $user_from_email['is_verified'] = true;
                $user_from_email['email_verified_at'] = now();
                if (isset($socialiteUser->refreshToken)) {
                    $user_from_email['google_refresh_token'] = $socialiteUser->refreshToken;
                }
                $user_from_email->save();
            } else {
                $newUser = User::create([
                    'google_id' => $socialiteUser->id,
                    'first_name' => $socialiteUser->user['given_name'] ?? null,
                    'last_name' => $socialiteUser->user['family_name'] ?? null,
                    'email' => $socialiteUser->email,
                    'google_token' => $socialiteUser->token,
                    'google_refresh_token' => $socialiteUser->refreshToken ?? null,
                    'avatar_url' => $socialiteUser->avatar_original ?? $socialiteUser->avatar,
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]);
                $newUser->google_id = $socialiteUser->id;
                $newUser->google_token = $socialiteUser->token;
                $newUser->avatar_url = $socialiteUser->avatar_original ?? $socialiteUser->avatar;
                if (isset($socialiteUser->refreshToken)) {
                    $newUser->google_refresh_token = $socialiteUser->refreshToken;
                }
                $newUser->is_verified = true;
                $newUser->email_verified_at = now();
                $newUser->save();
                $user_from_email = $newUser;
            }
            
            Auth::login($user_from_email);
            $authUser = Auth::user();
            $success['token'] = $authUser->createToken('User Token', ['user:bill:crud', 'user:payment:crud', 'user:notification:r'])->accessToken;
            $success['user'] = $user_from_email;

            return $this->sendResponse($success, 'User logged in successfully via google.');
        } catch (\Throwable $th) {
            Log::error("Socialite callback error: " . $th->getMessage());
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), null, 422);
            }

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
                'password' => PasswordValidation::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
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
            return $this->sendError('Token has expired', null, 400);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'password' => PasswordValidation::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
                'password_confirmation' => 'required|same:password',
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), null, 422);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return $this->sendResponse(null, 'Password changed successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
}
