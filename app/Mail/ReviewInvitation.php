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
        // Get website settings
        $settings = $this->getSettings();
        
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
                'websiteName' => $settings['website_name'] ?? 'Research Nexus',
            ],
        );
    }

    private function getSettings(): array
    {
        try {
            if (\Storage::disk('local')->exists('settings.json')) {
                return json_decode(\Storage::disk('local')->get('settings.json'), true) ?? [];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to read settings in email: ' . $e->getMessage());
        }
        return [];
    }

    public function attachments(): array
    {
        return [];
    }
}
