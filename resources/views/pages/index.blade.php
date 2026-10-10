@extends('layouts.admin')

@section('content')
    <h1 class="text-xl font-semibold text-gray-900">Pages</h1>

    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Blocks</th>
                    <th class="px-4 py-3">Updated</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($pages as $page)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $page->title }}</td>
                        <td class="px-4 py-3 text-gray-600">/{{ $page->slug }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $page->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($page->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $page->blocks_count }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $page->updated_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('pages.show', $page) }}" class="text-gray-600 hover:text-gray-900">View</a>
                            <a href="{{ route('pages.edit', $page) }}" class="ms-4 font-medium text-gray-900 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No pages yet. Sync your frontend manifest to create them.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
