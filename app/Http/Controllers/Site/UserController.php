<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Scopes\CurrentSite;
use App\Models\Site;
use App\Models\SiteRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The members of the current site. Users are global, so everything here goes
 * through the site's membership: a user who isn't a member is a 404, and
 * "remove" only takes them off this site. A member's name, email and password
 * can be changed only while this is their one site and they aren't a platform
 * admin, since those would also change them on other sites.
 *
 * Admins manage everyone but the site's owners; only owners change roles or
 * touch other owners (config/acl.php siteManage / siteOwner).
 */
class UserController extends Controller
{
    public function __construct(private CurrentSite $currentSite) {}

    public function index(): View
    {
        return view('users.index', [
            'members' => $this->site()->users()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function show(int $user): View
    {
        $member = $this->member($user);

        return view('users.show', [
            'member' => $member,
            'role' => $member->pivot->role,
            'canEditAccount' => $this->canEditAccount($member) && $this->canTouch($member),
            'canChangeRole' => $this->actingAsOwner(),
            'canRemove' => $this->canTouch($member),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->processPost($request);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        return $this->processPost($request, $this->member($user));
    }

    /**
     * Change the member's role on this site.
     */
    public function role(Request $request, int $user): RedirectResponse
    {
        $member = $this->member($user);

        $data = $request->validate([
            'role' => ['required', Rule::in(SiteRole::all())],
        ]);

        abort_if($member->is($request->user()), 422, 'You cannot change your own role.');

        $old = $member->pivot->role;

        $this->guardLastOwner($member, $data['role']);

        $this->site()->users()->updateExistingPivot($member->id, ['role' => $data['role']]);

        ActivityLog::write('user', $member->id, $member->name, 'role_changed', ['role' => ['old' => $old, 'new' => $data['role']]]);

        toast('success', "{$member->name} is now {$data['role']}.");

        return redirect()->route('users.show', $member->id);
    }

    /**
     * Set a new password. Left blank, one is generated and shown once.
     */
    public function resetPassword(Request $request, int $user): RedirectResponse
    {
        $member = $this->member($user);

        abort_unless($this->canEditAccount($member) && $this->canTouch($member), 403);

        $data = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $password = filled($data['password'] ?? null) ? $data['password'] : Str::password(16, symbols: false);

        $member->forceFill(['password' => $password])->save();

        ActivityLog::write('user', $member->id, $member->name, 'password_reset');

        return redirect()->route('users.show', $member->id)->with('shown_password', $password);
    }

    /**
     * Take the user off this site. Their account and other sites are untouched.
     */
    public function destroy(Request $request, int $user): RedirectResponse
    {
        $member = $this->member($user);

        abort_if($member->is($request->user()), 422, 'You cannot remove yourself from the site.');
        abort_unless($this->canTouch($member), 403);

        $this->guardLastOwner($member, null);

        $this->site()->users()->detach($member->id);

        ActivityLog::write('user', $member->id, $member->name, 'member_removed', ['role' => ['old' => $member->pivot->role, 'new' => null]]);

        toast('success', "{$member->name} removed from the site.");

        return redirect()->route('users.index');
    }

    /**
     * Create the member (or update their details). On create, an email that
     * already has an account is added to the site as is; a new email gets an
     * account with a generated password, shown once.
     */
    private function processPost(Request $request, ?User $member = null): RedirectResponse
    {
        if ($member !== null) {
            abort_unless($this->canEditAccount($member) && $this->canTouch($member), 403);

            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($member->id)],
            ]);

            $old = $member->only(['name', 'email']);
            $member->fill($data)->save();

            ActivityLog::write('user', $member->id, $member->name, 'updated', ActivityLog::diff($old, $member->only(['name', 'email'])));

            toast('success', 'Member saved.');

            return redirect()->route('users.show', $member->id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::in($this->actingAsOwner() ? SiteRole::all() : [SiteRole::ADMIN, SiteRole::EDITOR])],
        ]);

        $member = User::where('email', $data['email'])->first();

        if ($member !== null && $this->site()->users()->whereKey($member->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'This person is already a member of the site.']);
        }

        $shownPassword = null;

        if ($member === null) {
            $shownPassword = Str::password(16, symbols: false);
            $member = new User(['name' => $data['name'], 'email' => $data['email']]);
            $member->password = $shownPassword;
            $member->save();
        }

        $this->site()->users()->attach($member->id, ['role' => $data['role']]);

        ActivityLog::write('user', $member->id, $member->name, 'member_added', ['role' => ['old' => null, 'new' => $data['role']]]);

        toast('success', 'Member added.');

        return redirect()->route('users.show', $member->id)->with('shown_password', $shownPassword);
    }

    private function site(): Site
    {
        return $this->currentSite->get();
    }

    private function member(int $id): User
    {
        return $this->site()->users()->whereKey($id)->firstOrFail();
    }

    private function actingAsOwner(): bool
    {
        $user = auth()->user();

        return $user->isAdmin() || $user->roleOn($this->site()) === SiteRole::OWNER;
    }

    /**
     * Whether the signed-in user may change this member: owners only by owners.
     */
    private function canTouch(User $member): bool
    {
        return $member->pivot->role !== SiteRole::OWNER || $this->actingAsOwner();
    }

    private function canEditAccount(User $member): bool
    {
        return ! $member->isAdmin() && $member->sites()->count() === 1;
    }

    /**
     * A site must keep an owner: block demoting or removing the last one.
     * Pass the new role, or null when removing.
     */
    private function guardLastOwner(User $member, ?string $newRole): void
    {
        $isLastOwner = $member->pivot->role === SiteRole::OWNER
            && $newRole !== SiteRole::OWNER
            && $this->site()->owners()->count() === 1;

        abort_if($isLastOwner, 422, 'The site must keep at least one owner.');
    }
}
