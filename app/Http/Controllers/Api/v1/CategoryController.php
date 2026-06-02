<?php

namespace App\Http\Controllers\Api\v1;

use App\Filters\CategoryFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CategoryFilter $filter)
    {
        $cacheKey = 'client_categories_' . md5(request()->fullUrl());

        $jsonString = Cache::tags(['categories'])
            ->remember($cacheKey, now()->addDay(), function () use ($filter) {
                
                $categories = Category::filter($filter)->get();
                $response = $this->success(CategoryResource::collection($categories), '', 200);
                return $response->getContent();
            });

        return response($jsonString, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return $this->success(new CategoryResource($category), '', 200);
    }
}
