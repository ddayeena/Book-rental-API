<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Filters\Admin\ReviewFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Admin\Review\ReplyReviewRequest;
use App\Http\Resources\Api\v1\Admin\Review\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a paginated listing of all reviews in the system.
     */
    public function index(ReviewFilter $filter)
    {
        $reviews = Review::with(['user' => function ($query) {
            $query->withTrashed();
        }, 'book'])
            ->filter($filter)
            ->latest()
            ->apiPaginate();

        return $this->respondWithPagination(ReviewResource::collection($reviews));
    }

    /**
     * Remove the specified review (Moderation).
     */
    public function destroy(Review $review)
    {
        try {
            $review->delete();
            return $this->success(null, __('messages.review_deleted_successfully'), 200);
        } catch (\Exception $e) {
            return $this->error(__('messages.deletion_failed'), 400, $e->getMessage());
        }
    }

    /**
     * Reply to a review as an admin.
     */
    public function reply(ReplyReviewRequest $request, Review $review)
    {
        try {
            $review->update([
                'admin_reply' => $request->validated('admin_reply'),
            ]);

            $review->load('user');

            return $this->success(
                new ReviewResource($review),
                __('messages.review_replied_successfully'),
                200
            );
        } catch (\Exception $e) {
            return $this->error(__('messages.update_failed'), 400, $e->getMessage());
        }
    }
}
