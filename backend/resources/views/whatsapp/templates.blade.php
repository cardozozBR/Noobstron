@extends('layouts.app')

@section('content')
<style>
.whatsapp-templates-page{display:grid;gap:24px}
.whatsapp-templates-page .page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0}
.whatsapp-templates-page .page-header h1{margin:0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.whatsapp-templates-page .back-link{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151!important;font-size:13px;font-weight:700;text-decoration:none}
.whatsapp-templates-page .card{margin:0;padding:22px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.whatsapp-templates-page .create-form{display:grid;gap:18px}
.whatsapp-templates-page .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.whatsapp-templates-page .mb-3{display:grid;gap:7px;margin:0}
.whatsapp-templates-page .form-label{color:#374151;font-size:13px;font-weight:700}
.whatsapp-templates-page .form-control{width:100%;min-height:42px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font:inherit;font-size:14px;outline:none;box-shadow:0 1px 2px rgba(15,23,42,.035);transition:border-color 160ms ease,box-shadow 160ms ease}
.whatsapp-templates-page textarea.form-control{min-height:150px;resize:vertical}
.whatsapp-templates-page .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.whatsapp-templates-page .form-actions{display:flex;justify-content:flex-end;padding-top:18px;border-top:1px solid #e5e7eb}
.whatsapp-templates-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
.whatsapp-templates-page .btn-primary{border:1px solid transparent;background:var(--primary);color:#fff}
.whatsapp-templates-page .templates-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.whatsapp-templates-page .template-card{padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.035)}
.whatsapp-templates-page .template-card strong{display:block;color:#111827;font-size:15px}
.whatsapp-templates-page .template-card p{margin:10px 0 0;color:#4b5563;font-size:13px;line-height:1.55;white-space:pre-wrap}
.whatsapp-templates-page .empty-state{padding:44px 24px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;text-align:center}
@media(max-width:800px){.whatsapp-templates-page .page-header{align-items:flex-start;flex-direction:column}.whatsapp-templates-page .form-grid,.whatsapp-templates-page .templates-grid{grid-template-columns:1fr}.whatsapp-templates-page .card{padding:18px}}
</style>

<div class="whatsapp-templates-page">
    <div class="page-header">
        <div>
            <h1>{{ __('whatsapp.templates') }}</h1>
        </div>

        <a
            href="{{ route('whatsapp.index') }}"
            class="back-link"
        >
            ← {{ __('whatsapp.history') }}
        </a>
    </div>

    <div class="card">
        <form
            method="POST"
            action="{{ route('whatsapp.templates.store') }}"
            class="create-form"
        >
            @csrf

            <div class="form-grid">
                <div class="mb-3">
                    <label class="form-label">
                        {{ __('whatsapp.template_name') }}
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        {{ __('whatsapp.provider') }}
                    </label>

                    <input
                        type="text"
                        name="provider"
                        class="form-control"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        {{ __('whatsapp.provider_template_name') }}
                    </label>

                    <input
                        type="text"
                        name="provider_template_name"
                        class="form-control"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        {{ __('whatsapp.language') }}
                    </label>

                    <input
                        type="text"
                        name="language"
                        class="form-control"
                    >
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    {{ __('whatsapp.template_body') }}
                </label>

                <textarea
                    name="body_template"
                    class="form-control"
                    rows="5"
                    required
                ></textarea>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary">
                    {{ __('whatsapp.create_template') }}
                </button>
            </div>
        </form>
    </div>

    @forelse ($templates as $template)
        @if ($loop->first)
            <div class="templates-grid">
        @endif

        <div class="template-card">
            <strong>{{ $template->name }}</strong>
            <p>{{ $template->body_template }}</p>
        </div>

        @if ($loop->last)
            </div>
        @endif
    @empty
        <div class="card">
            <div class="empty-state">
                {{ __('whatsapp.no_templates') }}
            </div>
        </div>
    @endforelse
</div>
@endsection
