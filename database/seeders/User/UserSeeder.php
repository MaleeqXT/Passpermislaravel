<?php

namespace Database\Seeders\User;

use App\Enums\V2\Admin\User\UserRolesEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Admin
        $user = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'name' => 'Super Admin',
            'email' => 'admin@pf.com',
            'password' => Hash::make('passwordrs'),
            'media' => 'https://robohash.org/' . fake()->uuid . '?size=200x200',

        ]);
        $user->assignRole(UserRolesEnum::ADMIN->value);

        // student
        $user = User::create([
            'first_name' => 'soufiyan',
            'last_name' => 'First candidat',
            'name' => 'Soufiyan student',
            'email' => 'student@pf.com',
            'password' => Hash::make('passwordrs'),
            'media' => 'https://robohash.org/' . fake()->uuid . '?size=200x200',
        ]);
        $user->student()->create();
        $user->assignRole(UserRolesEnum::STUDENT->value);

        $user = User::create([
            'first_name' => 'Ali',
            'last_name' => 'El Kassimi',
            'name' => 'Ali El Kassimi',
            'email' => 'ali@pf.com',
            'password' => Hash::make('passwordrs'),
            'media' => 'https://robohash.org/' . fake()->uuid . '?size=200x200',
        ]);
        $user->student()->create();
        $user->assignRole(UserRolesEnum::STUDENT->value);

        $user = User::create([
            'first_name' => 'Eva',
            'last_name' => 'Martinise',
            'name' => 'Eva Martinise',
            'email' => 'martin@pf.com',
            'password' => Hash::make('passwordrs'),
            'media' => 'https://robohash.org/' . fake()->uuid . '?size=200x200',
        ]);
        $user->student()->create();
        $user->assignRole(UserRolesEnum::STUDENT->value);


        // monitor
        $user = User::create([
            'first_name' => 'Reda',
            'last_name' => 'Moniteur',
            'name' => 'Reda Moniteur',
            'email' => 'monitor@pf.com',
            'password' => Hash::make('passwordrs'),
            'media' => 'https://robohash.org/' . fake()->uuid . '?size=200x200',
        ]);
        $monitor = $user->monitor()->create();
        $monitor->details()->create();
        $user->assignRole(UserRolesEnum::MONITOR->value);


        // monitor
        $user = User::create([
            'first_name' => 'Morad',
            'last_name' => 'BMW',
            'name' => 'Morad BMW',
            'email' => 'morad@pf.com',
            'password' => Hash::make('passwordrs'),
            'media' => 'https://robohash.org/' . fake()->uuid . '?size=200x200',
        ]);
        $monitor = $user->monitor()->create();
        $monitor->details()->create();
        $user->assignRole(UserRolesEnum::MONITOR->value);
    }
}
