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
        User::createOrFirst([
            'name' => 'Admin',
            'email' => 'admin@admin',
            'password' => bcrypt('admin'),
        ]);
    }
}
