<?php

namespace App\Repositories\Impl\Auth;

use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\OTPRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\User;
use App\Notifications\OTPNotification;
use App\Repositories\Interfaces\Auth\AuthInterface;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthInterface
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $request->validated($request->all());
        $credentials = $request->validated();
        $authenticated = Auth::attempt($credentials);
        // dd($authenticated);
        if ($authenticated) {
            return $this->error('Invalid credentials', 401);
        }

        // $user = Auth::user();
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return $this->error('User not found', 404);
        }

        $token = $user->createToken('API token for '.$user->email)->plainTextToken;

        return $this->success('Authenticated', [
            'token' => $token,
        ], 200);
    }

    public function signup(RegisterRequest $request)
    {
        $request->validated($request->all());

        $image = $request->file('photo')->getClientOriginalName();
        $path = $request->file('photo')->storeAs('profile', $request->first_name.$request->last_name.'_'.$image, 'public');

        $user = User::create([
            'photo' => $path,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'location' => $request->location,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (! $user) {
            return $this->error('Registration failed', 400);
        }
        $token = $user->createToken('API Token')->plainTextToken;
        if (! $token) {
            return $this->error('Unable to create token', 400);
        }
        $user->generateCode();

        $user->notify(new OTPNotification);

        return $this->success('Registration successful', [
            'token' => $token,
        ], 201);
    }

    public function OTPCode(OTPRequest $request)
    {
        $request->validated($request->all());

        $user = auth()->user();

        if ($user && $request->code == $user->code && $user->isCodeValid()) {
            $user->resetCode();
            $user->update(['email_verified_at' => now()]);

            return $this->ok('Verified successfully', 200);
        }

        return $this->error('Invalid code', 401);
    }

    public function resendOTPCode()
    {
        $user = auth()->user();

        if ($user && $user->email_verified_at == null) {
            $user->generateCode();

            return $this->ok('OTP code resent successfully', 200);
        }

        return $this->error('You have already verified', 400);
    }

    public function logout()
    {
        $user = auth()->user();
        $user->currentAccessToken()->delete();

        return $this->ok('Logout successful', 200);
    }
}
