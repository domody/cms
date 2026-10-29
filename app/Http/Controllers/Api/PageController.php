<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Models\PageStatus;

class PageController extends Controller
{
    public function show(string $slug): PageResource
    {
        $page = Page::with('blocks')
            ->where('status', PageStatus::PUBLISHED)
            ->where('slug', $slug)
            ->firstOrFail();

        return new PageResource($page);
    }
}
