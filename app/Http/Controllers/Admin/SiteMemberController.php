<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteMemberController extends Controller
{
    /**
     * Add an existing user to a site, or change their role if already a member.
     */
    public function store(Request $request, Site $site): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'role' => ['required', Rule::in(SiteRole::all())],
        ]);

        $site->users()->syncWithoutDetaching([$data['user_id'] => ['role' => $data['role']]]);

        toast('success', 'Member saved.');

        return redirect()->route('admin.sites.show', $site->slug);
    }

    public function destroy(Site $site, User $user): RedirectResponse
    {
        $site->users()->detach($user->id);

        toast('success', "{$user->name} removed from {$site->name}.");

        return redirect()->route('admin.sites.show', $site->slug);
    }
}
