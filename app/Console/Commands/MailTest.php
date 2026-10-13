<?php

namespace App\Console\Commands;

use App\Mail\SystemTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Uji konfigurasi SMTP: kirim email percobaan ke alamat tujuan.
 * php artisan ypl:mail-test tujuan@lokon.or.id
 */
class MailTest extends Command
{
    protected $signature = 'ypl:mail-test {to : Alamat email tujuan uji}';

    protected $description = 'Kirim email uji untuk memverifikasi konfigurasi SMTP';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        $mailer = (string) config('mail.default');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Alamat tidak valid: {$to}");

            return self::FAILURE;
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("Mailer aktif '{$mailer}' — email tidak benar-benar dikirim.");
            $this->line('Set MAIL_MAILER=smtp + kredensial di .env untuk pengiriman nyata.');
        }

        try {
            Mail::to($to)->send(new SystemTestMail($mailer));
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Email uji terkirim ke {$to} (mailer: {$mailer}).");
        $this->line('Bila tidak masuk, periksa folder spam dan kredensial SMTP.');

        return self::SUCCESS;
    }
}
