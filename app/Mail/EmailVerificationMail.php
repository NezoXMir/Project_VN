<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly string $code,
        public readonly string $verifyLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Подтвердите ваш email — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view:      'emails.verify-email',
            text:      'emails.verify-email-text',
        );
    }
}
