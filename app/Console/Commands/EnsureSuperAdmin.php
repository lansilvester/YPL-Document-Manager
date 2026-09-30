<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Membuat/menjamin tepat 1 akun Super Admin (milik developer).
 * php artisan ypl:superadmin --email=dev@lokon.or.id [--name="Developer YPL"] [--force]
 */
class EnsureSuperAdmin extends Command
{
    protected $signature = 'ypl:superadmin {--email=superadmin@lokon.or.id : Email Super Admin} {--name=Developer YPL : Nama Super Admin} {--force : Pindahkan peran SA ke email ini bila SA lain sudah ada}';

    protected $description = 'Buat atau pastikan satu-satunya akun Super Admin';

    public function handle(): int
    {
        $email = strtolower((string) $this->option('email'));
        $existing = User::where('role', 'superadmin')->get();

        if ($existing->isNotEmpty() && ! $existing->contains('email', $email)) {
            if (! $this->option('force')) {
                $this->error('Super Admin sudah ada: '.$existing->pluck('email')->join(', ').'. Gunakan --force untuk memindahkan.');

                return self::FAILURE;
            }
            foreach ($existing as $old) {
                $old->update(['role' => 'admin']);
            }
            $this->warn('Peran Super Admin lama diturunkan menjadi admin.');
        }

        $password = Str::random(16);
        $user = User::updateOrCreate(['email' => $email], [
            'name' => (string) $this->option('name'),
            'password' => $password,
            'role' => 'superadmin',
            'ypl_verified' => true,
        ]);

        $this->info("Super Admin: {$user->email}");
        $this->info("Kata sandi baru: {$password}");
        $this->warn('Simpan kata sandi ini dan segera ganti setelah masuk.');

        return self::SUCCESS;
    }
}
