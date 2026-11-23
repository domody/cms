<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteTokenController extends Controller
{
    /**
     * Abilities granted by each token type: `read` serves content, `sync`
     * additionally lets the frontend push its manifest.
     *
     * @var array<string, array<int, string>>
     */
    const TYPES = [
        'read' => ['read'],
        'sync' => ['read', 'sync'],
    ];

    /**
     * Issue a token owned by the site. The plain text is flashed to the next
     * page once; only a hash is stored.
     */
    public function store(Request $request, Site $site): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
        ]);

        $token = $site->issueToken($data['name'], self::TYPES[$data['type']]);

        return redirect()
            ->route('admin.sites.show', $site->slug)
            ->with('new_token', ['name' => $data['name'], 'plain' => $token->plainTextToken]);
    }

    public function destroy(Site $site, PersonalAccessToken $token): RedirectResponse
    {
        abort_unless($token->site_id === $site->id, 404);

        $token->delete();

        toast('success', 'Token revoked.');

        return redirect()->route('admin.sites.show', $site->slug);
    }
}
