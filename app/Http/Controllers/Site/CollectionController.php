<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CollectionDefinition;
use App\Models\CollectionEntry;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        return view('collections.index', [
            'collections' => CollectionDefinition::orderBy('label')->get(),
            'entryCounts' => CollectionEntry::selectRaw('collection_key, count(*) as total')
                ->groupBy('collection_key')
                ->pluck('total', 'collection_key'),
        ]);
    }

    public function show(CollectionDefinition $collection): View
    {
        return view('collections.show', ['collection' => $collection]);
    }

    public function create(CollectionDefinition $collection): View
    {
        return view('collections.form', ['collection' => $collection, 'entry' => null]);
    }

    public function edit(CollectionDefinition $collection, CollectionEntry $entry): View
    {
        abort_unless($entry->collection_key === $collection->key, 404);

        return view('collections.form', ['collection' => $collection, 'entry' => $entry]);
    }
}
