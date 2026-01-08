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
               // "fcm_token"=>'ejnE_TreQhOAup44Ih7rDI:APA91bH1LqKlQXFOxZIntGG8eMgzdmvtmVIOUKwQZZ2G9YxSCSuXtX0XLH0BKCmykiL5E02waesAcD1sFGouV8CRG9Uu-205T-14fYcr1Kr2LUzmrGTePCQ',

            ],
            (object)[
                "first_name" => "Kamal",
                "last_name" => "Alkhateeb",
                "phone_number" => "0911111112",
                "date_of_birth" => "2005-1-4",
                "role" => 'renter',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
               // "fcm_token"=>'dxJqjQBxTwyBlpALL9JNck:APA91bGzNXLGpIMJdw7aZnmuwci_QejSmOpKyJc4ld6vBkG6UNe34R2ZsekN_p8zabtqyU10R_hqbM04p1Rj_K9EXGbzyKhGPVOlzQP3OfIwq7WvU1EFSlk'
            ],
            (object)[
                "first_name" => "Loay",
                "last_name" => "Hammodeh",
                "phone_number" => "0911111113",
                "date_of_birth" => "2005-1-4",
                "role" => 'renter',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
                //"fcm_token"=>'ejnE_TreQhOAup44Ih7rDI:APA91bH1LqKlQXFOxZIntGG8eMgzdmvtmVIOUKwQZZ2G9YxSCSuXtX0XLH0BKCmykiL5E02waesAcD1sFGouV8CRG9Uu-205T-14fYcr1Kr2LUzmrGTePCQ'
            ],
            (object)[
                "first_name" => "Zuhair",
                "last_name" => "Mahjoub",
                "phone_number" => "0911111114",
                "date_of_birth" => "2005-1-4",
                "role" => 'owner',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
               // "fcm_token"=>'dxJqjQBxTwyBlpALL9JNck:APA91bGzNXLGpIMJdw7aZnmuwci_QejSmOpKyJc4ld6vBkG6UNe34R2ZsekN_p8zabtqyU10R_hqbM04p1Rj_K9EXGbzyKhGPVOlzQP3OfIwq7WvU1EFSlk'
            ],
            (object)[
                "first_name" => "Jad",
                "last_name" => "Jado",
                "phone_number" => "0900000000",
                "date_of_birth" => "2005-1-4",
                "role" => 'admin',
                'status' => 'accepted',
                "password" => Hash::make("00000000"),
               // "fcm_token"=>''
            ],
        ];

        foreach ($users as $user) {
            User::create(['first_name' => $user->first_name, 'last_name' => $user->last_name, 'date_of_birth' => $user->date_of_birth, 'status' => $user->status, 'phone_number' => $user->phone_number, 'role' => $user->role, 'password' => $user->password, /* 'fcm_token'=>$user->fcm_token*/]);
        }
    }
}
