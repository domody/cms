<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Whole-CMS area (/admin), outside any single site.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard');
    }
}
