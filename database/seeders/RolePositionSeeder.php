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

        // Buat akun Admin utama
        User::firstOrCreate(
            ['email' => 'admin@rotiwonosari.test'],
            [
                'name'     => 'Admin System',
                'password' => 'password',
                'role_id'  => Role::where('name', 'Admin')->value('id'),
            ]
        );

        // Ambil ID Roles
        $managerRoleId  = Role::where('name', 'Manager')->value('id');
        $karyawanRoleId = Role::where('name', 'Karyawan')->value('id');

        // Daftar akun dummy per posisi (Password semuanya: 'password')
        $dummyUsers = [
            [
                'email'       => 'sm@rotiwonosari.test',
                'name'        => 'Budi (Store Manager)',
                'position'    => 'Store Manager',
                'role_id'     => $managerRoleId,
            ],
            [
                'email'       => 'spv@rotiwonosari.test',
                'name'        => 'Siti (Supervisor)',
                'position'    => 'SPV',
                'role_id'     => $karyawanRoleId,
            ],
            [
                'email'       => 'frontliner@rotiwonosari.test',
                'name'        => 'Dewi (Frontliner / Kasir)',
                'position'    => 'Frontliner',
                'role_id'     => $karyawanRoleId,
            ],
            [
                'email'       => 'produksi@rotiwonosari.test',
                'name'        => 'Joko (Tim Produksi)',
                'position'    => 'Produksi',
                'role_id'     => $karyawanRoleId,
            ],
            [
                'email'       => 'driver@rotiwonosari.test',
                'name'        => 'Andi (Driver)',
                'position'    => 'Driver',
                'role_id'     => $karyawanRoleId,
            ],
            [
                'email'       => 'sales@rotiwonosari.test',
                'name'        => 'Rina (Sales & Marketing)',
                'position'    => 'Sales',
                'role_id'     => $karyawanRoleId,
            ],
        ];

        foreach ($dummyUsers as $data) {
            $positionId = Position::where('name', $data['position'])->value('id');

            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'        => $data['name'],
                    'password'    => 'password',
                    'role_id'     => $data['role_id'],
                    'position_id' => $positionId,
                    'is_active'   => true,
                ]
            );
        }
    }
}