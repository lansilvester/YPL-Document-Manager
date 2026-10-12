<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan governance (BAB 13): dokumen memasuki masa review atau diarsipkan.
 * Channel `database` untuk lonceng in-app; `mail` ditambahkan otomatis hanya
 * bila mailer produksi sudah dikonfigurasi (bukan `log`/`array`).
 */
class GovernanceNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $extraLines
     */
    public function __construct(
        public Document $document,
        public string $kind,
        public string $title,
        public string $message,
        public array $extraLines = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! in_array(config('mail.default'), ['log', 'array', null], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->message);

        foreach ($this->extraLines as $line) {
            $mail->line($line);
        }

        return $mail
            ->action('Buka Dokumen', route('governance.show', $this->document))
            ->line('Email otomatis dari YPL Smart Digital Library — tidak perlu dibalas.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'title' => $this->title,
            'message' => $this->message,
            'url' => route('governance.show', $this->document),
        ];
    }
}
