<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // role KHÔNG nằm trong $fillable nên phải gán tay -> đúng chủ đích bảo mật
        foreach ([
            ['Quản trị viên', 'admin@shop.test', 'Admin@123', 'admin'],
            ['Khách Một',     'user1@shop.test', 'User@123',  'user'],
            ['Khách Hai',     'user2@shop.test', 'User@123',  'user'],
        ] as [$name, $email, $pass, $role]) {
            $u = new User(['full_name' => $name, 'email' => $email, 'password' => $pass, 'phone' => '0900000000']);
            $u->role = $role;
            $u->save();
        }
    }
}
