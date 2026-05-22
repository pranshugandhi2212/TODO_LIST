<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ContactSubmissionMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $contactData
     */
    public function __construct(
        public array $contactData,
        private readonly ?Carbon $submittedAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Request: '.$this->contactData['subject'],
            replyTo: [
                new Address($this->contactData['email'], $this->contactData['name']),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-submission',
            with: [
                'brandName' => $this->brandName(),
                'supportEmail' => (string) config('contact.support_email', $this->contactData['email']),
                'submittedAt' => ($this->submittedAt ?? now())->format('d M Y, h:i A'),
                'contactData' => $this->contactData,
                'categoryLabel' => $this->formatLabel($this->contactData['category']),
                'priorityLabel' => $this->formatLabel($this->contactData['priority']),
                'priorityBadgeColor' => $this->priorityBadgeColor($this->contactData['priority']),
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function brandName(): string
    {
        $configuredBrandName = (string) config('contact.brand_name', '');

        if ($configuredBrandName !== '') {
            return $configuredBrandName;
        }

        $appName = (string) config('app.name', 'Yono Todolist');

        return $appName === 'Laravel' ? 'Yono Todolist' : $appName;
    }

    private function formatLabel(string $value): string
    {
        return str($value)
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }

    private function priorityBadgeColor(string $priority): string
    {
        return match ($priority) {
            'urgent' => '#DC2626',
            'high' => '#EA580C',
            'low' => '#0F766E',
            default => '#2563EB',
        };
    }
}
