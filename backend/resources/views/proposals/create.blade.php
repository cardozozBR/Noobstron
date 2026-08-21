@extends('layouts.app')

@section('title', __('proposals.create_title'))

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <a href="{{ route('proposals.index') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-900" style="color:#4b5563;">
            ← {{ __('proposals.cancel') }}
        </a>

        <h1 class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
            {{ __('proposals.create_title') }}
        </h1>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <form method="POST" action="{{ route('proposals.store') }}" class="space-y-6">
            @csrf
            @include('proposals._form')

            <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
                <a href="{{ route('proposals.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50" style="color:#374151;">
                    {{ __('proposals.cancel') }}
                </a>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[var(--primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                    {{ __('proposals.save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
