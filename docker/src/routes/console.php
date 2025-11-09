<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Artisan::command('make:first-user', function () {
    $email = 'admin@example.com';
    $password = 'secret';
    $role = 1;

    if (User::where('email', $email)->exists()) {
        $this->warn("User with email {$email} already exists.");
        return;
    }

    $user = User::create([
        'email' => $email,
        'password' => Hash::make($password),
        'role' => $role,
        'created_at' => now(),
        'created_by' => null,   // ✅ no creator
        'updated_by' => null,   // ✅ no updater
    ]);

    $this->info("✅ User created: {$user->email} (password: {$password})");
})->describe('Creates the first admin user');
