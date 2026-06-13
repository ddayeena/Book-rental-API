<?php

namespace App\Services;

use App\Enums\RentalStatus;
use App\Models\Review;
use App\Models\User;
use Exception;

class ReviewService
{
    /**
     * Create a new review with business rule validations.
     */
    public function createReview(User $user, array $data): Review
    {
        // If the user has not read the book, they cannot leave a review.
        $hasRead = $user->rentals()
            ->where('book_id', $data['book_id'])
            ->whereIn('status', [RentalStatus::COMPLETED]) 
            ->exists();

        if (!$hasRead) {
            throw new Exception(__('messages.must_read_to_review'));
        }

        // If the user has already left a review for this book, they cannot leave another one.
        $existingReview = Review::where('user_id', $user->id)
            ->where('book_id', $data['book_id'])
            ->exists();

        if ($existingReview) {
            throw new Exception(__('messages.review_already_exists'));
        }

        return Review::create([
            'user_id' => $user->id,
            'book_id' => $data['book_id'],
            'rating'  => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    /**
     * Update an existing review.
     */
    public function updateReview(User $user, Review $review, array $data): Review
    {
        if ($review->user_id !== $user->id) {
            throw new Exception(__('messages.not_your_review'));
        }

        $review->update($data);

        return $review;
    }

    /**
     * Delete an existing review.
     */
    public function deleteReview(User $user, Review $review): void
    {
        if ($review->user_id !== $user->id) {
            throw new Exception(__('messages.not_your_review'));
        }

        $review->delete();
    }
}