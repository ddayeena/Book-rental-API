<?php

namespace App\Http\Controllers\Api\v1;

use App\Filters\ReviewFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Review\IndexReviewRequest;
use App\Http\Requests\Api\v1\Review\StoreReviewRequest;
use App\Http\Requests\Api\v1\Review\UpdateReviewRequest;
use App\Http\Resources\Api\v1\Review\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Display a paginated listing of reviews for a specific book.
     */
    public function index(IndexReviewRequest $request, ReviewFilter $filter)
    {
        $data = $request->validated();
        $reviews = Review::with('user')
            ->filter($filter)
            ->where('book_id', $data['bookId'])
            ->latest()
            ->apiPaginate();

        return $this->respondWithPagination(ReviewResource::collection($reviews));
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request)
    {
        try {
            $review = $this->reviewService->createReview(
                $request->user(),
                $request->validated()
            );

            return $this->success(
                new ReviewResource($review),
                __('messages.review_created_successfully'),
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        try {
            $updatedReview = $this->reviewService->updateReview(
                $request->user(),
                $review,
                $request->validated()
            );

            return $this->success(
                new ReviewResource($updatedReview),
                __('messages.review_updated_successfully'),
                200
            );
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === __('messages.not_your_review') ? 403 : 400;
            return $this->error($e->getMessage(), $statusCode);
        }
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Request $request, Review $review)
    {
        try {
            $this->reviewService->deleteReview($request->user(), $review);

            return $this->success(null, __('messages.review_deleted_successfully'), 200);
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === __('messages.not_your_review') ? 403 : 400;
            return $this->error($e->getMessage(), $statusCode);
        }
    }
}
