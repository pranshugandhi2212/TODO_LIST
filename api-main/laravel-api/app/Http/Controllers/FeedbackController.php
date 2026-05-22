<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Feedback::query()
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', 'max:255', Rule::unique('feedback', 'email')],
                'subject' => 'required|string|max:255',
                'message' => 'required|string|min:20|max:5000',
                'rating' => 'required|integer|min:1|max:5',
            ],
            [
                'email.unique' => 'One review per email is allowed. This email already submitted a review.',
            ]
        );

        $feedback = Feedback::query()->create($validated);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => $feedback,
        ], 201);
    }
}
