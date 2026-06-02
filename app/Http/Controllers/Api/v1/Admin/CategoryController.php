<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Filters\CategoryFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Api\v1\Admin\Categories\UpdateCategoryRequest;
use App\Http\Resources\Api\v1\Admin\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CategoryFilter $filter)
    {
        $categories = Category::filter($filter)->get();
        return $this->success(CategoryResource::collection($categories), '', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());
        return $this->success(new CategoryResource($category), __('messages.created'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return $this->success(new CategoryResource($category), '', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return $this->success(new CategoryResource($category), __('messages.updated'), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return $this->success(null, __('messages.deleted'), 200);
    }
}
