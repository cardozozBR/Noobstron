@extends('layouts.app')

@section('content')
<div>
    <div>
        <h1>{{ __('leads.new') }}</h1>

        <a href="{{ route('leads.index') }}">
            {{ __('leads.actions.back') }}
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('leads.store') }}"
    >
        @csrf

        @include('leads._form')

        <button type="submit">
            {{ __('leads.actions.create') }}
        </button>

        <a href="{{ route('leads.index') }}">
            {{ __('leads.actions.cancel') }}
        </a>
    </form>
</div>
@endsection
