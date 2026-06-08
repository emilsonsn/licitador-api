<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::where('created_at', '<', now())->delete();

        User::updateOrCreate([
            'email' => 'arnaldotadeu.ep@gmail.com',
        ],
        [
            'name' => 'Admin',            
            'password' => bcrypt('SDL@2026sdl'),
            'is_active' => true,
            'is_admin' => true
        ]);
    }
}
