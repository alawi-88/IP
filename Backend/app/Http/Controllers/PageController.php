<?php

namespace App\Http\Controllers;

use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Resources\Json\JsonResource;

class PageController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Page $page): JsonResource
    {
        return new PageResource($page);
    }
}
