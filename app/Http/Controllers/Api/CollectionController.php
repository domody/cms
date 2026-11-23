<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionDefinition;
use App\Models\CollectionEntry;
use App\Models\PageStatus;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    /**
     * Every published entry of a collection, in the order set in the CMS.
     */
    public function index(string $key): JsonResponse
    {
        CollectionDefinition::where('key', $key)->firstOrFail();

        return response()->json(['data' => CollectionEntry::publishedPayloads($key)]);
    }

    public function show(string $key, string $slug): JsonResponse
    {
        CollectionDefinition::where('key', $key)->firstOrFail();

        $entry = CollectionEntry::where('collection_key', $key)
            ->where('status', PageStatus::PUBLISHED)
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => CollectionEntry::payloads(collect([$entry]))[0]]);
    }
}
