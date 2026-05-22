<?php

namespace App\Http\Controllers;

use App\Mail\ContactSubmissionMail;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:general,sales,support,partnership,feedback'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'max:5000'],
            'subscribe' => ['nullable', 'boolean'],
            'rating' => ['nullable', 'integer', 'min:0', 'max:5'],
        ]);

        $contactData = [
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'phone' => $this->cleanOptionalValue($validated['phone'] ?? null),
            'company' => $this->cleanOptionalValue($validated['company'] ?? null),
            'jobTitle' => $this->cleanOptionalValue($validated['jobTitle'] ?? null),
            'subject' => trim((string) $validated['subject']),
            'category' => (string) $validated['category'],
            'priority' => (string) ($validated['priority'] ?? 'normal'),
            'message' => trim((string) $validated['message']),
            'subscribe' => (bool) ($validated['subscribe'] ?? false),
            'rating' => isset($validated['rating']) ? (int) $validated['rating'] : null,
        ];

        Comment::query()->create([
            'name' => $contactData['name'],
            'email' => $contactData['email'],
            'comment' => $this->buildCommentTranscript($contactData),
        ]);

        Mail::to($this->notificationAddress())->send(new ContactSubmissionMail($contactData));

        return response()->json([
            'message' => 'Contact request sent successfully.',
            'data' => [
                'name' => $contactData['name'],
                'email' => $contactData['email'],
                'subject' => $contactData['subject'],
                'category' => $contactData['category'],
                'priority' => $contactData['priority'],
            ],
        ], 201);
    }

    private function cleanOptionalValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function buildCommentTranscript(array $contactData): string
    {
        $lines = [
            'Contact submission received from the website.',
            'Subject: '.$contactData['subject'],
            'Category: '.$this->formatLabel($contactData['category']),
            'Priority: '.$this->formatLabel($contactData['priority']),
            'Phone: '.($contactData['phone'] ?? 'Not provided'),
            'Company: '.($contactData['company'] ?? 'Not provided'),
            'Job Title: '.($contactData['jobTitle'] ?? 'Not provided'),
            'Newsletter Subscription: '.($contactData['subscribe'] ? 'Yes' : 'No'),
        ];

        if ($contactData['rating'] !== null) {
            $lines[] = 'Rating: '.$contactData['rating'].'/5';
        }

        $lines[] = '';
        $lines[] = 'Message:';
        $lines[] = $contactData['message'];

        return implode("\n", $lines);
    }

    private function formatLabel(string $value): string
    {
        return str($value)
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }

    private function notificationAddress(): string
    {
        $configuredAddress = (string) config('contact.notification.address', '');

        if ($configuredAddress !== '') {
            return $configuredAddress;
        }

        return (string) config('mail.from.address', 'hello@example.com');
    }
}
