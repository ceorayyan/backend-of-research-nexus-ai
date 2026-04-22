<?php

namespace App\Mail;

use App\Models\Review;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Review $review,
        public User $inviter,
        public ?User $invitee = null,
        public ?string $message = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->inviter->name} invited you to join a review: {$this->review->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.review-invitation',
            with: [
                'review' => $this->review,
                'inviter' => $this->inviter,
                'invitee' => $this->invitee,
                'message' => $this->message,
                'acceptUrl' => $this->invitee 
                    ? url("/reviews/{$this->review->id}/accept")
                    : url("/signup?redirect=/reviews/{$this->review->id}/accept"),
                'isRegistered' => $this->invitee !== null,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
