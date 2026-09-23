<?php

namespace Database\Seeders\User;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UsersRolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $Rolesitems = [
            [
                'name' => 'admin',
                'guard_name' => 'web',
            ],
            [
                'name' => 'student',
                'guard_name' => 'web',
            ],
            [
                'name' => 'monitor',
                'guard_name' => 'web',
            ]
        ];

        /*
         * Add Permission Items
         *
         */
        foreach ($Rolesitems as $Role) {

            Role::create([
                'name' => $Role['name'],
                'guard_name' => $Role['guard_name'],
            ]);
        }
    }
}
