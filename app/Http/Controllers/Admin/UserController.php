<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLevel;
use App\Models\Site;
use App\Models\SiteRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('sites')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'sites' => Site::orderBy('name')->get(),
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user->load('sites'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->processPost($request);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        return $this->processPost($request, $user);
    }

    /**
     * Set a new password for the user. Left blank, one is generated and shown
     * once on the next page, so the admin can pass it on.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $password = filled($data['password'] ?? null) ? $data['password'] : Str::password(16, symbols: false);

        $user->forceFill(['password' => $password])->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('shown_password', $password);
    }

    /**
     * Disable (cannot log in, tokens stop working) or re-enable a user.
     */
    public function toggleDisabled(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot disable your own account.');

        if ($user->isDisabled()) {
            $user->enable();
            toast('success', "{$user->name} enabled.");
        } else {
            $user->disable();
            toast('success', "{$user->name} disabled.");
        }

        return redirect()->route('admin.users.show', $user);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');

        $user->delete();

        toast('success', "{$user->name} deleted.");

        return redirect()->route('admin.users.index');
    }

    /**
     * Validate and save a user. On create the user can be added to a site and
     * given a password (generated and shown once when left blank).
     */
    private function processPost(Request $request, ?User $user = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'access_level' => ['required', Rule::in(AccessLevel::all())],
            'password' => $user === null ? ['nullable', 'string', 'min:8', 'max:255'] : ['prohibited'],
            'site_id' => $user === null ? ['nullable', 'integer', Rule::exists('sites', 'id')] : ['prohibited'],
            'role' => $user === null ? ['required_with:site_id', 'nullable', Rule::in(SiteRole::all())] : ['prohibited'],
        ]);

        if ($user !== null && $user->is($request->user()) && $data['access_level'] !== $user->access_level) {
            abort(422, 'You cannot change your own access level.');
        }

        $user ??= new User;
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        $user->access_level = $data['access_level'];

        $shownPassword = null;

        if (! $user->exists) {
            $shownPassword = filled($data['password'] ?? null) ? null : Str::password(16, symbols: false);
            $user->password = ($data['password'] ?? null) ?: $shownPassword;
        }

        $created = ! $user->exists;
        $user->save();

        if ($created && filled($data['site_id'] ?? null)) {
            $user->sites()->attach($data['site_id'], ['role' => $data['role']]);
        }

        toast('success', 'User saved.');

        return redirect()
            ->route('admin.users.show', $user)
            ->with('shown_password', $shownPassword);
    }
}
