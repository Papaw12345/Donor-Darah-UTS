<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = getenv('DEMO_ADMIN_PASSWORD');

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException(
                'DEMO_ADMIN_PASSWORD must be set to a non-empty password before running AdminSeeder.'
            );
        }

        $email = 'admin.demo@udd.test';
        $akun = Akun::query()->where('email', $email)->first();

        if ($akun === null) {
            Akun::create([
                'email' => $email,
                'password_hash' => Hash::make($password),
                'peran' => 'ADMIN',
                'status_akun' => 'AKTIF',
            ]);

            return;
        }

        if ($akun->peran !== 'ADMIN') {
            throw new RuntimeException(
                'The demo Admin email is already used by an account with a different role.'
            );
        }

        if ($akun->status_akun !== 'AKTIF') {
            throw new RuntimeException(
                'The demo Admin account already exists but is not active.'
            );
        }

        if (! is_string($akun->password_hash) || ! Hash::check($password, $akun->password_hash)) {
            throw new RuntimeException(
                'The demo Admin account already exists with different credentials.'
            );
        }
    }
}
