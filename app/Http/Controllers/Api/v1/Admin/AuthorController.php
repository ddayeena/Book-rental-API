<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Filters\AuthorFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Admin\Authors\StoreAuthorRequest;
use App\Http\Requests\Api\v1\Admin\Authors\UpdateAuthorRequest;
use App\Http\Resources\Api\v1\Admin\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AuthorFilter $filter)
    {
        $authors = Author::filter($filter)->apiPaginate(); 
        return $this->respondWithPagination(AuthorResource::collection($authors));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuthorRequest $request)
    {
        $author = Author::create($request->validated());
        return $this->success(new AuthorResource($author), __('messages.created'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Author $author)
    {
        return $this->success(new AuthorResource($author), '', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuthorRequest $request, Author $author)
    {
        $author->update($request->validated());
        return $this->success(new AuthorResource($author), __('messages.updated'), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Author $author)
    {
        $author->delete();
        return $this->success(null, __('messages.deleted'), 200);
    }
}
