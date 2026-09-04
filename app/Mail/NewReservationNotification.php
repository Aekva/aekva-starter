<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReservationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $primaryTextColor;

    public function __construct(
        public Reservation $reservation,
        public string $businessName,
        public string $primaryColor = '#18181b',
        public ?string $logoPath = null,
    ) {
        /*
         * Détermine automatiquement si le texte
         * sur la couleur principale doit être
         * noir ou blanc.
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

            /*
             * Si le professionnel clique sur Répondre,
             * il répond directement au client.
             */
            replyTo: $this->reservation->customer_email
                ? [
                    new Address(
                        $this->reservation->customer_email,
                        $this->reservation->customer_name
                    ),
                ]
                : [],

            subject: 'Nouvelle réservation - ' .
                $this->reservation->customer_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.reservations.new-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}