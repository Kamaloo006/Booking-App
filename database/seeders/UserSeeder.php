<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            (object)[
                "first_name" => "Mazen",
                "last_name" => "Alrefai",
                "phone_number" => "094047681",
                "date_of_birth" => "2005-1-4",
                "role" => 'owner',
                "password" => Hash::make("123123123"),

            ],
            (object)[
                "first_name" => "Kamal",
                "last_name" => "Alkhateeb",
                "phone_number" => "094047682",
                "date_of_birth" => "2005-1-4",
                "role" => 'renter',
                "password" => Hash::make("123123123")
            ],
            (object)[
                "first_name" => "Loay",
                "last_name" => "Hammodeh",
                "phone_number" => "094047683",
                "date_of_birth" => "2005-1-4",
                "role" => 'renter',
                "password" => Hash::make("123123123")
            ],
            (object)[
                "first_name" => "Zuhair",
                "last_name" => "Mahjoub",
                "phone_number" => "094047684",
                "date_of_birth" => "2005-1-4",
                "role" => 'owner',
                "password" => Hash::make("123123123")
            ],
        ];

        foreach ($users as $user) {
            User::create(['first_name' => $user->first_name, 'last_name' => $user->last_name, 'date_of_birth' => $user->date_of_birth, 'phone_number' => $user->phone_number, 'role' => $user->role, 'password' => $user->password]);
        }
    }
}
