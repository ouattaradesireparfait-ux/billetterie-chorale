<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $e = config('admin.email');
        $p = config('admin.password');
        if (! $e || ! $p) {
            throw new \RuntimeException('Configurez ADMIN_EMAIL et ADMIN_PASSWORD.');
        }User::updateOrCreate(['email' => $e], ['name' => 'Administrateur', 'password' => Hash::make($p), 'is_admin' => true, 'is_active' => true]);
    }
}
