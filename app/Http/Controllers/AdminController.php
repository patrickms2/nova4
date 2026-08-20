<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $request->validated($request->all());

        // $image = $request->file('photo')->getClientOriginalName();
        // $path = $request->file('photo')->storeAs('profile', $request->first_name .$request->last_name . '_' . $image, 'public');

        $user = User::create([
            // 'photo' => $path,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'user_type' => 'admin',
            // 'location' => $request->location,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'password_salt' => Hash::make($request->password),
        ]);

        if (! $user) {
            return $this->error('Registration failed', 400);
        }
        // $token = $user->createToken('API Token')->plainTextToken;
        // if (!$token) {
        // return $this->error('Unable to create token', 400);
        // }
        // $user->generateCode();

        // $user->notify(new OTPNotification());

        // return $this->success('Registration successful', [
        //     // 'token' => $token,
        // ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        //
    }
}
