<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\BlockDefinition;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('pages.index', [
            'pages' => Page::withCount('blocks')->orderBy('title')->get(),
        ]);
    }

    /**
     * Read-only view: every block in order (including hidden ones) with its
     * content, plus the exact JSON the public API hands to the frontend.
     */
    public function show(Request $request, Page $page): View
    {
        $page->load('blocks');

        return view('pages.show', [
            'page' => $page,
            'definitions' => BlockDefinition::all()->keyBy('key'),
            'apiPayload' => (new PageResource($page))->resolve($request),
        ]);
    }

    public function edit(Page $page): View
    {
        return view('pages.edit', ['page' => $page]);
    }
}
