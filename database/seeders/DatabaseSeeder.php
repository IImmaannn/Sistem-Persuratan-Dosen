<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DosenProfile;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Role sesuai SRS & Kaido Kit (Spatie)
        // Pastikan nama role sesuai dengan ENUM di migration User yang kita buat sebelumnya
        $roles = [
            'Admin',
            'Dosen',
            'Operator_Surat',
            'Operator_Nomor',
            'Supervisor',
            'Manager',
            'Wakil_Dekan',
            'Dekan'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // 2. Buat Akun ADMIN
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator Sistem',
            'email' => 'admin@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Password default
            'role' => 'Admin', // Untuk kolom Enum
            'gender' => 'Laki-laki',
        ]);
        $admin->assignRole('Admin'); // Untuk Spatie/Filament

        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen1',
            'name' => 'Budi Santoso, M.Kom',
            'email' => 'dosen@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Laki-laki',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '199001012022011001', // NIP Contoh
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);
        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen2',
            'name' => 'Mahon Jo, M.Kom',
            'email' => 'mahonjo@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Laki-laki',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '200501012023030401', // NIP Contoh
            'tempat_lahir' => 'Soul',
            'tanggal_lahir' => '2005-01-01',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);
        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen3',
            'name' => 'Choi Sangho, M.Kom',
            'email' => 'choisangho@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Laki-laki',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '200501022023030401', // NIP Contoh
            'tempat_lahir' => 'Soul',
            'tanggal_lahir' => '2005-01-02',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);
        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen4',
            'name' => 'Deokbong Kim, M.Kom',
            'email' => 'deokbongkim@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Laki-laki',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '200501032023030401', // NIP Contoh
            'tempat_lahir' => 'Soul',
            'tanggal_lahir' => '2005-01-03',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);
        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen5',
            'name' => 'Kang Hannam, M.Kom',
            'email' => 'kanghannam@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Laki-laki',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '200501112023030401', // NIP Contoh
            'tempat_lahir' => 'Soul',
            'tanggal_lahir' => '2005-01-11',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);
        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen6',
            'name' => 'Owen Knight M.Kom',
            'email' => 'owenknight@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Laki-laki',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '200501122023030401', // NIP Contoh
            'tempat_lahir' => 'Soul',
            'tanggal_lahir' => '2005-01-12',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);
        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen7',
            'name' => 'Jahyeon Jo, M.Kom',
            'email' => 'jahyeonjo@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Laki-laki',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '200501132023030401', // NIP Contoh
            'tempat_lahir' => 'Soul',
            'tanggal_lahir' => '2005-01-13',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);
        // 3. Buat Akun DOSEN (Plus Data Profil)
        $dosen = User::create([
            'username' => 'dosen8',
            'name' => 'Shelly Scott, M.Kom',
            'email' => 'sehllyscott@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dosen',
            'gender' => 'Perempuan',
        ]);
        $dosen->assignRole('Dosen');

        // Isi tabel dosen_profiles (Wajib sesuai ERD)
        DosenProfile::create([
            'user_id' => $dosen->id,
            'nip' => '200501142023030401', // NIP Contoh
            'tempat_lahir' => 'Soul',
            'tanggal_lahir' => '2005-01-14',
            'golongan' => 'III/b',
            'pangkat' => 'Penata Muda Tk. I',
            'jabatan' => 'Lektor',
        ]);

        // 4. Buat Akun OPERATOR SURAT (OCS - Verifikator Awal)
        $ocs = User::create([
            'username' => 'ocs',
            'name' => 'Staff Akademik (Cek)',
            'email' => 'ocs@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Operator_Surat',
            'gender' => 'Perempuan',
        ]);
        $ocs->assignRole('Operator_Surat');

        // 5. Buat Akun OPERATOR NOMOR (OPS - Finishing)
        $ops = User::create([
            'username' => 'ops',
            'name' => 'Staff Tata Usaha (Nomor)',
            'email' => 'ops@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Operator_Nomor',
            'gender' => 'Laki-laki',
        ]);
        $ops->assignRole('Operator_Nomor');

        // 6. Buat Akun PIMPINAN (Approval Berjenjang)
        
        // Supervisor
        $spv = User::create([
            'username' => 'supervisor',
            'name' => 'Ibu Supervisor',
            'email' => 'spv@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Supervisor',
            'gender' => 'Perempuan',
        ]);
        $spv->assignRole('Supervisor');

        // Manager
        $mgr = User::create([
            'username' => 'manager',
            'name' => 'Bapak Manager',
            'email' => 'manager@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Manager',
            'gender' => 'Laki-laki',
        ]);
        $mgr->assignRole('Manager');

        // Wakil Dekan
        $wadek = User::create([
            'username' => 'wadek',
            'name' => 'Dr. Wakil Dekan',
            'email' => 'wadek@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Wakil_Dekan',
            'gender' => 'Laki-laki',
        ]);
        $wadek->assignRole('Wakil_Dekan');

        // Dekan (Final Approval)
        $dekan = User::create([
            'username' => 'dekan',
            'name' => 'Prof. Dekan',
            'email' => 'dekan@filkom.edu',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'Dekan',
            'gender' => 'Laki-laki',
        ]);
        $dekan->assignRole('Dekan');

        $this->call([
            ConfigSeeder::class,
            // Seeder lainnya...
        ]);
    }
}