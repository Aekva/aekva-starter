<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ReservationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public string $primaryTextColor;

    public function __construct(
        public Reservation $reservation,
        public string $businessName,
        public ?string $businessPhone = null,
        public ?string $businessEmail = null,
        public ?string $businessAddress = null,
        public string $primaryColor = '#18181b',
        public ?string $logoPath = null,
    ) {
        /*
         * Détermine automatiquement si le texte doit
         * être noir ou blanc sur la couleur principale.
         */

        $hex = ltrim($this->primaryColor, '#');

        if (strlen($hex) !== 6) {
            $hex = '18181b';
            $this->primaryColor = '#18181b';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (
            (0.2126 * $r) +
            (0.7152 * $g) +
            (0.0722 * $b)
        ) / 255;

        $this->primaryTextColor =
            $luminance > 0.6
                ? '#18181b'
                : '#ffffff';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                $this->businessName
            ),
    
            replyTo: $this->businessEmail
                ? [
                    new Address(
                        $this->businessEmail,
                        $this->businessName
                    ),
                ]
                : [],
    
            subject: 'Confirmation de votre réservation - ' . $this->businessName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.reservations.confirmed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}