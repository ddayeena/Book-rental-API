<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 

abstract class QueryFilter
{
    protected Request $request;
    protected Builder $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request->query() as $name => $value) {
            $methodName = Str::camel($name);

            if (method_exists($this, $methodName) && $value !== null && $value !== '') {
                $this->$methodName($value);
            }
        }

        return $this->builder;
    }
}