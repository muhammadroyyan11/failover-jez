<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@jezpro.id'],
            [
                'name'              => 'Super Admin',
                'email'             => 'admin@jezpro.id',
                'password'          => Hash::make('Admin@12345'),
                'role'              => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Superadmin created: admin@jezpro.id / Admin@12345');
    }
}
