<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Landing: admins go to the admin dashboard, customers to the first
     * site they belong to.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $site = $user->defaultSite();

        abort_if($site === null, 403, 'Your account is not a member of any site.');

        return redirect()->route('dashboard', ['site' => $site->slug]);
    }
}
