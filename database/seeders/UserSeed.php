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
        User::updateOrCreate([
            'email' => 'admin@admin',
        ],
        [
            'name' => 'Admin',            
            'password' => bcrypt('admin'),
            'is_active' => true,
            'is_admin' => true
        ]);
    }
}
