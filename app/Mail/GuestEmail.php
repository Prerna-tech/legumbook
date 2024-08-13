<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestEmail extends Mailable
{
    use Queueable, SerializesModels;
     public $details;
    /**
     * Create a new message instance.
     */
    public function __construct($details)
    {
        $this->details=$details;
    }

    /**
     * Get the message envelope.
     */

     public function build()
     {
         return $this->subject('Guest Question')
         ->markdown('guest_question')
         ->with(['details'=>$this->details]);
     }
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Guest Email',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

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
