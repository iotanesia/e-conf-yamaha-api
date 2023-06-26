<?php

namespace Database\Seeders;

use App\Constants\Group;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(!User::where('username','admin')->first()){
            User::insert([
                'app_name' => 'Superadmin',
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('Qzeyr198z@/='),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'group_id' => Group::ADMIN // superadmin
            ]);
        }
    }
}
