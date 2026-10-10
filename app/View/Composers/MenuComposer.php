<?php

namespace App\View\Composers;

use Illuminate\View\View;

class MenuComposer
{
    public function compose(View $view): void
    {
        $view->with('sidebarMenu', [
            [
                'type' => 'link',
                'text' => 'Dashboard',
                'url' => route('dashboard'),
                'route' => 'dashboard',
            ],
            [
                'type' => 'link',
                'text' => 'Pages',
                'url' => route('pages.index'),
                'route' => 'pages.*',
            ],
        ]);
    }
}
