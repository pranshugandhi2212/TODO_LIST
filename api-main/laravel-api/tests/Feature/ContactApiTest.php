<?php

namespace Tests\Feature;

use App\Mail\ContactSubmissionMail;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_contact_request_and_trigger_email(): void
    {
        Mail::fake();
        Config::set('contact.notification.address', 'owner@example.com');

        $response = $this->postJson('/api/contact', [
            'name' => 'Rahul Patel',
            'email' => 'rahul@example.com',
            'phone' => '+91 9876543210',
            'company' => 'Yono Labs',
            'jobTitle' => 'Founder',
            'subject' => 'Need a demo',
            'category' => 'sales',
            'priority' => 'high',
            'message' => 'Please share pricing and onboarding details.',
            'subscribe' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Contact request sent successfully.')
            ->assertJsonPath('data.subject', 'Need a demo')
            ->assertJsonPath('data.category', 'sales');

        $savedComment = Comment::query()->latest()->first();

        $this->assertNotNull($savedComment);
        $this->assertSame('Rahul Patel', $savedComment->name);
        $this->assertSame('rahul@example.com', $savedComment->email);
        $this->assertStringContainsString('Subject: Need a demo', $savedComment->comment);
        $this->assertStringContainsString('Company: Yono Labs', $savedComment->comment);

        Mail::assertSent(ContactSubmissionMail::class, function (ContactSubmissionMail $mail): bool {
            return $mail->hasTo('owner@example.com');
        });
    }

    public function test_contact_request_requires_basic_fields(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', [
            'name' => '',
            'email' => 'invalid-email',
            'subject' => '',
            'message' => '',
            'category' => 'general',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'subject',
                'message',
            ]);

        Mail::assertNothingSent();
    }
}
