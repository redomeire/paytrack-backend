<?php

namespace App\Mail;

use App\Models\User;
use App\Models\bills;
use App\Models\payments;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentSuccess extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected bills $bill,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Success',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $user = User::find($this->bill->user_id);
        $payment = payments::where('bill_id', $this->bill->id)->first();
        return new Content(
            markdown: 'mail.payment-success',
            with: [
                'bill' => $this->bill,
                'user' => $user,
                'payment' => $payment,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
