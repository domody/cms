@extends('layouts.admin')

@section('content')
    <div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                Sign out
            </button>
        </form>
    </div>
@endsection
