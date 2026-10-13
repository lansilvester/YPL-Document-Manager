<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Kode reset kata sandi (sekali pakai). Dikirim HANYA ke email terdaftar —
 * kode tidak pernah ditampilkan di respons HTTP, sehingga tidak dapat
 * disadap dari halaman publik.
 */
class PasswordResetCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public int $ttlMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode Reset Kata Sandi — Lokon Dokumen Digital YPL');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-code',
            with: [
                'code' => $this->code,
                'ttl' => $this->ttlMinutes,
            ],
        );
    }
}
