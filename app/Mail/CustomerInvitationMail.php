<?php

namespace App\Mail;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public ?Garage $garage = null
    ) {}

    public function envelope(): Envelope
    {
        $garageName = $this->garage?->name ?? config('app.name', 'PistonCRM');
        return new Envelope(
            subject: "Welcome to {$garageName} - Your Account & Login Details",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer_invitation',
        );
    }
}
