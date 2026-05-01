<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $password,
        public string $appUrl = ''
    ) {}

    public function envelope(): Envelope
    {
        $settings = $this->getSettings();
        $appName = $settings['website_name'] ?? 'StataNex.Ai';
        
        return new Envelope(
            subject: "Welcome to {$appName} - Your Account is Ready",
        );
    }

    public function content(): Content
    {
        $settings = $this->getSettings();
        $appName = $settings['website_name'] ?? 'StataNex.Ai';
        
        // Get frontend URL from config or use provided one
        $appUrl = $this->appUrl;
        if (!$appUrl) {
            $frontendUrls = config('app.frontend_url', 'http://localhost:3000');
            // If multiple URLs are configured (comma-separated), use the first one
            $appUrl = explode(',', $frontendUrls)[0];
            $appUrl = trim($appUrl);
        }
        
        return new Content(
            view: 'emails.user-welcome',
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'appName' => $appName,
                'appUrl' => $appUrl,
                'loginUrl' => "{$appUrl}/login",
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
