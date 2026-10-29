<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(): View
    {
        return view('admin.sites.index', [
            'sites' => Site::with('owners')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sites.create');
    }

    public function show(Site $site): View
    {
        $site->load('users');

        return view('admin.sites.show', [
            'site' => $site,
            'tokens' => $site->tokens()->latest()->get(),
            'addableUsers' => User::whereNotIn('id', $site->users->modelKeys())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->processPost($request);
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        return $this->processPost($request, $site);
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $request->validate([
            'confirm_slug' => ['required', Rule::in([$site->slug])],
        ], [
            'confirm_slug.in' => 'Type the site slug exactly to delete it.',
        ]);

        $site->delete();

        toast('success', "Site \"{$site->name}\" deleted.");

        return redirect()->route('admin.sites.index');
    }

    /**
     * Validate and save a site. The slug is only taken on create: it is the
     * first URL segment, so changing it would break every link into the site.
     */
    private function processPost(Request $request, ?Site $site = null): RedirectResponse
    {
        $this->prepareRequest($request, $site);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $site === null
                ? ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(Site::RESERVED_SLUGS), 'unique:sites,slug']
                : ['prohibited'],
            'revalidate_url' => ['nullable', 'url:http,https', 'max:2048'],
            'revalidate_secret' => ['nullable', 'string', 'max:255'],
        ], [
            'slug.not_in' => 'That slug is reserved.',
            'slug.prohibited' => 'The slug cannot be changed.',
        ]);

        $fillable = $site === null ? ['name', 'slug', 'revalidate_url'] : ['name', 'revalidate_url'];

        $site ??= new Site;
        $site->fill(collect($data)->only($fillable)->all());

        if (filled($data['revalidate_secret'] ?? null)) {
            $site->revalidate_secret = $data['revalidate_secret'];
        }

        $site->save();

        toast('success', 'Site saved.');

        return redirect()->route('admin.sites.show', $site->slug);
    }

    /**
     * Fill a missing slug from the name (new sites only).
     */
    private function prepareRequest(Request $request, ?Site $site): void
    {
        if ($site === null && blank($request->input('slug'))) {
            $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        }

        $request->merge(['slug' => $site === null ? Str::lower((string) $request->input('slug')) : null]);
    }
}
