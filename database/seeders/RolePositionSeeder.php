<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePositionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Admin', 'Manager', 'Karyawan'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        foreach (['Store Manager', 'Frontliner', 'Produksi', 'Driver', 'Sales', 'SPV'] as $name) {
            Position::firstOrCreate(['name' => $name]);
        }

        User::firstOrCreate(
            ['email' => 'admin@rotiwonosari.test'],
            [
                'name'     => 'Admin',
                'password' => 'password',
                'role_id'  => Role::where('name', 'Admin')->value('id'),
            ]
        );
    }
}