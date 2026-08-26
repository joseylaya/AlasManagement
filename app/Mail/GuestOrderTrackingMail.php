<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestOrderTrackingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $trackingUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Track your ALAS order {$this->order->order_number}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.guest-order-tracking');
    }
}
