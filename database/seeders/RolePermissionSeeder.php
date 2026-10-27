<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view',

            'vclaim.view',
            'vclaim.create',
            'vclaim.edit',
            'vclaim.delete',

            'antrean-rs.view',
            'antrean-rs.create',
            'antrean-rs.edit',
            'antrean-rs.delete',

            'apotek.view',
            'apotek.create',
            'apotek.edit',
            'apotek.delete',

            'user-management.view',
            'user-management.create',
            'user-management.edit',
            'user-management.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superadmin  = Role::firstOrCreate(['name' => 'superadmin']);
        $bpjs        = Role::firstOrCreate(['name' => 'bpjs']);
        $farmasi     = Role::firstOrCreate(['name' => 'farmasi']);
        $pendaftaran = Role::firstOrCreate(['name' => 'pendaftaran']);
        $clientRole  = Role::firstOrCreate(['name' => 'client']);

        $superadmin->syncPermissions(Permission::all());

        $bpjs->syncPermissions([
            'dashboard.view',
            'vclaim.view',
            'vclaim.create',
            'vclaim.edit',
            'antrean-rs.view',
            'antrean-rs.create',
            'antrean-rs.edit',
        ]);

        $farmasi->syncPermissions([
            'dashboard.view',
            'apotek.view',
            'apotek.create',
            'apotek.edit',
        ]);

        $pendaftaran->syncPermissions([
            'dashboard.view',
            'antrean-rs.view',
            'antrean-rs.create',
        ]);

        $demoRole = Role::firstOrCreate(['name' => 'demo']);
        $demoRole->syncPermissions([
            'dashboard.view',
            'vclaim.view',
            'antrean-rs.view',
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@simrs.local'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $user->assignRole('superadmin');

        $demoUser = User::firstOrCreate(
            ['email' => 'demo@simrs.local'],
            [
                'name' => 'Pengguna Uji Coba (Demo 50 Req/Hari)',
                'password' => bcrypt('password'),
            ]
        );

        $demoUser->assignRole('demo');

        // Sample Antrean Hari Ini untuk Demo APM Kiosk & Testing
        $today = date('Y-m-d');
        $nowMs = (int) (microtime(true) * 1000);
        \App\Models\Antrean::firstOrCreate(
            ['kode_booking' => 'JKN261001'],
            [
                'jenispasien' => 'JKN',
                'nomorkartu' => '0001827364521',
                'nik' => '3201124508890002',
                'nama_pasien' => 'Ny. Rahmawati',
                'nohp' => '081234567890',
                'kodepoli' => 'INT',
                'namapoli' => 'PENYAKIT DALAM',
                'kodedokter' => '101',
                'namadokter' => 'dr. Budi Santoso, Sp.PD',
                'norm' => 'RM-084920',
                'tanggalperiksa' => $today,
                'jampraktek' => '08:00-12:00',
                'jeniskunjungan' => 1,
                'nomorreferensi' => '0112B0011026P000123',
                'nomorantrean' => 'INT-001',
                'angkaantrean' => 1,
                'estimasidilayani' => $nowMs + 900000,
                'status' => 'Menunggu',
                'sumber' => 'Mobile JKN',
                'task1_waktu' => $nowMs - 3600000,
                'task2_waktu' => $nowMs - 3500000,
            ]
        );
    }
}
