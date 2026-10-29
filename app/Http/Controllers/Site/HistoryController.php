<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Everything that changed on the site, newest first, optionally narrowed
     * to one kind of record, one record (type + subject) or one person.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(ActivityLog::TYPES))],
            'subject' => ['nullable', 'integer'],
            'user' => ['nullable', 'integer'],
        ]);

        return view('history.index', [
            'logs' => ActivityLog::query()
                ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('subject_type', $type))
                ->when($filters['subject'] ?? null, fn ($query, $id) => $query->where('subject_id', $id))
                ->when($filters['user'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'people' => ActivityLog::query()->whereNotNull('user_id')->distinct()->orderBy('actor_name')->pluck('actor_name', 'user_id'),
            'filters' => $filters,
        ]);
    }
}
