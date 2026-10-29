@extends('layouts.admin', [
    'title' => 'Dashboard',
    'pageTitle' => 'Dashboard',
    'pageDescription' => 'Overview of your content.',
    'breadcrumbs' => [
        ['label' => 'Home'],
        ['label' => 'Dashboard'],
    ],
])

@section('content')
    {{-- TEMP: toast test buttons. Remove with App\Livewire\ToastPreview. --}}
    <livewire:toast-preview />
@endsection
