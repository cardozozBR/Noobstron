@extends('layouts.app')

@section('content')
<style>
.import-create-page{display:grid;gap:24px;max-width:900px;margin:0 auto}
.import-create-page h1{margin:0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.import-create-page .card{margin:0;padding:24px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.import-create-page form{display:grid;gap:18px}
.import-create-page form>div{display:grid;gap:7px}
.import-create-page label{color:#374151;font-size:13px;font-weight:700}
.import-create-page input[type=file],.import-create-page select{width:100%;min-height:42px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font:inherit;font-size:14px;outline:none;box-shadow:0 1px 2px rgba(15,23,42,.035)}
.import-create-page input[type=file]:focus,.import-create-page select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.import-create-page button{justify-self:end;min-height:40px;padding:9px 14px;border:0;border-radius:10px;background:var(--primary);color:#fff;font-size:13px;font-weight:700;cursor:pointer}
.import-create-page .error-text{color:#b91c1c;font-size:12px;font-weight:600}
</style>

<div class="import-create-page">
    <div>
        <h1>{{ __('imports.new') }}</h1>
    </div>

    <div class="card">
        <form
            method="POST"
            action="{{ route('imports.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            <div>
                <label for="file">
                    {{ __('imports.file') }}
                </label>

                <input
                    id="file"
                    type="file"
                    name="file"
                    accept=".csv,.txt"
                    required
                >

                @error('file')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="target">
                    {{ __('imports.target') }}
                </label>

                <select
                    id="target"
                    name="target"
                    required
                >
                    @foreach ($targets as $target)
                        <option
                            value="{{ $target->value }}"
                        >
                            {{
                                __('imports.'
                                    . $target->value)
                            }}
                        </option>
                    @endforeach
                </select>

                @error('target')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="delimiter">
                    {{ __('imports.delimiter') }}
                </label>

                <select
                    id="delimiter"
                    name="delimiter"
                    required
                >
                    <option value=",">
                        {{ __('imports.comma') }}
                    </option>

                    <option value=";">
                        {{ __('imports.semicolon') }}
                    </option>
                </select>
            </div>

            <button type="submit">
                {{ __('imports.upload') }}
            </button>
        </form>
    </div>
</div>
@endsection
