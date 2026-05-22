<?php

namespace Tests\Feature;

use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_store_feedback(): void
    {
        $response = $this->postJson('/api/feedbacks', [
            'name' => 'Manisha Sharma',
            'email' => 'manisha@example.com',
            'subject' => 'Smooth daily planning',
            'message' => 'The dashboard keeps my tasks clear and the reminders help me stay on track every day.',
            'rating' => 5,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Review submitted successfully.')
            ->assertJsonPath('data.subject', 'Smooth daily planning')
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('feedback', [
            'email' => 'manisha@example.com',
            'subject' => 'Smooth daily planning',
        ]);
    }

    public function test_feedbacks_can_be_listed(): void
    {
        Feedback::query()->create([
            'name' => 'Anita',
            'email' => 'anita@example.com',
            'subject' => 'Helpful workflow',
            'message' => 'I use it to manage client work and the flow feels much easier than before.',
            'rating' => 4,
        ]);

        $response = $this->getJson('/api/feedbacks');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Helpful workflow');
    }

    public function test_duplicate_feedback_email_is_rejected(): void
    {
        Feedback::query()->create([
            'name' => 'Existing User',
            'email' => 'repeat@example.com',
            'subject' => 'Already sent',
            'message' => 'This review already exists in the system with enough detail to pass validation.',
            'rating' => 4,
        ]);

        $response = $this->postJson('/api/feedbacks', [
            'name' => 'Existing User',
            'email' => 'repeat@example.com',
            'subject' => 'Trying again',
            'message' => 'I am trying to send the same feedback from the frontend again with enough detail.',
            'rating' => 5,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
