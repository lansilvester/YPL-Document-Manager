<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email uji konfigurasi SMTP (diagnostik). Dikirim oleh `php artisan ypl:mail-test`.
 */
class SystemTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $mailerName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Uji SMTP — Lokon Dokumen Digital YPL');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-test',
            with: [
                'mailer' => $this->mailerName,
                'waktu' => now()->toDateTimeString(),
            ],
        );
    }
}
