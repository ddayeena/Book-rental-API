<?php

namespace App\Http\Controllers\Api\v1;

use App\Filters\AuthorFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AuthorFilter $filter)
    {
        $cacheKey = 'client_authors_' . md5(request()->fullUrl());

        $jsonString = Cache::tags(['authors'])
            ->remember($cacheKey, now()->addDay(), function () use ($filter) {
                
                $authors = Author::filter($filter)->apiPaginate();
                $response = $this->respondWithPagination(AuthorResource::collection($authors));
                return $response->getContent();
            });

        return response($jsonString, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Author $author)
    {
        return $this->success(new AuthorResource($author), '', 200);
    }
}
