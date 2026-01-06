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
                "phone_number" => "0911111111",
                "date_of_birth" => "2005-1-4",
                "role" => 'owner',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
                "fcm_token"=>'cOAd7nyQSICFnhzbTgeDmS:APA91bEt5HRQWpFImEFS-NDDCVmX7mj1XVmQD740qwLam7FQYWzjXjhs1oVTQZkXRO_zAYDUoL-O-A1jvSwKpgv90NmU6XrElx7K2fPmbET77lN4JJ5Jvfk',

            ],
            (object)[
                "first_name" => "Kamal",
                "last_name" => "Alkhateeb",
                "phone_number" => "0911111112",
                "date_of_birth" => "2005-1-4",
                "role" => 'renter',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
                "fcm_token"=>'eCWCFdzCSf2IZkUr0_XtPW:APA91bGR6Zrh8ngpDHy5-9CpXmtZHXq3GjCx8ofmFvXoOyha9nIE4HJWp2pfvqnE5Hdrc_eK-64n2hPPqKYZE9GVhlbURWYD9DyAvkHF7pJdMc0-6Vn1ufg'
            ],
            (object)[
                "first_name" => "Loay",
                "last_name" => "Hammodeh",
                "phone_number" => "0911111113",
                "date_of_birth" => "2005-1-4",
                "role" => 'renter',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
                "fcm_token"=>'cOAd7nyQSICFnhzbTgeDmS:APA91bEt5HRQWpFImEFS-NDDCVmX7mj1XVmQD740qwLam7FQYWzjXjhs1oVTQZkXRO_zAYDUoL-O-A1jvSwKpgv90NmU6XrElx7K2fPmbET77lN4JJ5Jvfk'
            ],
            (object)[
                "first_name" => "Zuhair",
                "last_name" => "Mahjoub",
                "phone_number" => "0911111114",
                "date_of_birth" => "2005-1-4",
                "role" => 'owner',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
                "fcm_token"=>'eCWCFdzCSf2IZkUr0_XtPW:APA91bGR6Zrh8ngpDHy5-9CpXmtZHXq3GjCx8ofmFvXoOyha9nIE4HJWp2pfvqnE5Hdrc_eK-64n2hPPqKYZE9GVhlbURWYD9DyAvkHF7pJdMc0-6Vn1ufg'
            ],
            (object)[
                "first_name" => "Jad",
                "last_name" => "Jado",
                "phone_number" => "0900000000",
                "date_of_birth" => "2005-1-4",
                "role" => 'admin',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
                "fcm_token"=>'cOAd7nyQSICFnhzbTgeDmS:APA91bEt5HRQWpFImEFS-NDDCVmX7mj1XVmQD740qwLam7FQYWzjXjhs1oVTQZkXRO_zAYDUoL-O-A1jvSwKpgv90NmU6XrElx7K2fPmbET77lN4JJ5Jvfk'
            ],
        ];

        foreach ($users as $user) {
            User::create(['first_name' => $user->first_name, 'last_name' => $user->last_name, 'date_of_birth' => $user->date_of_birth, 'status' => $user->status, 'phone_number' => $user->phone_number, 'role' => $user->role, 'password' => $user->password, 'fcm_token'=>$user->fcm_token]);
        }
    }
}
