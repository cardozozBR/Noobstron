@extends('layouts.app')

@section('content')
<style>
.whatsapp-create-page{display:grid;gap:24px;max-width:900px;margin:0 auto}
.whatsapp-create-page h1{margin:0;font-size:30px;line-height:1.15;letter-spacing:-.025em}
.whatsapp-create-page .card{margin:0;padding:24px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.whatsapp-create-page form{display:grid;gap:18px}
.whatsapp-create-page .mb-3{display:grid;gap:7px;margin:0}
.whatsapp-create-page .form-label{color:#374151;font-size:13px;font-weight:700}
.whatsapp-create-page .form-control{width:100%;min-height:42px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font:inherit;font-size:14px;outline:none;box-shadow:0 1px 2px rgba(15,23,42,.035);transition:border-color 160ms ease,box-shadow 160ms ease}
.whatsapp-create-page textarea.form-control{min-height:150px;resize:vertical}
.whatsapp-create-page .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.whatsapp-create-page .mt-2{margin-top:8px;display:flex;align-items:center;gap:8px}
.whatsapp-create-page .btn{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:9px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
.whatsapp-create-page .btn-outline-primary,.whatsapp-create-page .btn-outline-secondary{border:1px solid #d1d5db;background:#fff;color:#374151}
.whatsapp-create-page .btn-primary{border:1px solid transparent;background:var(--primary);color:#fff}
.whatsapp-create-page .btn-sm{min-height:36px;padding:7px 10px;font-size:12px}
.whatsapp-create-page .form-actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:8px;padding-top:18px;border-top:1px solid #e5e7eb}
</style>

<div class="whatsapp-create-page">
    <div>
        <h1>{{ __('whatsapp.new_message') }}</h1>
    </div>

    <div class="card">
        <form
            method="POST"
            action="{{ route('whatsapp.store') }}"
        >
            @csrf

            <div class="mb-3">
                <label class="form-label">
                    {{ __('whatsapp.phone') }}
                </label>

                <input
                    type="text"
                    name="phone"
                    value="{{ old('phone') }}"
                    class="form-control"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">
                    {{ __('whatsapp.recipient_name') }}
                </label>

                <input
                    type="text"
                    name="recipient_name"
                    value="{{ old('recipient_name') }}"
                    class="form-control"
                >
            </div>

            <div class="mb-3">
                <label class="form-label">
                    {{ __('whatsapp.provider') }}
                </label>

                <input
                    type="text"
                    name="provider"
                    value="{{ old('provider') }}"
                    class="form-control"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">
                    {{ __('whatsapp.message') }}
                </label>

                <textarea
                    id="whatsapp_body"
                    name="body"
                    class="form-control"
                    rows="6"
                    required
                >{{ old('body') }}</textarea>

                @if (
                    app(\App\Support\TenantCapabilities::class)->enabled(
                        app(\App\Services\TenantContext::class)->get(),
                        \App\Enums\Feature::AI
                    )
                )
                    @can('ai.use')
                        <div class="mt-2">
                            <button
                                type="button"
                                id="whatsapp_ai_rewrite"
                                class="btn btn-outline-primary btn-sm"
                                data-url="{{ route('ai.rewrite') }}"
                            >
                                {{ __('whatsapp.ai_rewrite') }}
                            </button>

                            <span
                                id="whatsapp_ai_status"
                                class="ms-2"
                                aria-live="polite"
                            ></span>
                        </div>
                    @endcan
                @endif
            </div>

            <div class="form-actions">
                <button
                    type="submit"
                    name="action"
                    value="save"
                    class="btn btn-outline-secondary"
                >
                    {{ __('whatsapp.save') }}
                </button>

                @can('whatsapp.send')
                    <button
                        type="submit"
                        name="action"
                        value="send"
                        class="btn btn-primary"
                    >
                        {{ __('whatsapp.send_now') }}
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('whatsapp_ai_rewrite');
    const body = document.getElementById('whatsapp_body');
    const status = document.getElementById('whatsapp_ai_status');

    if (!button || !body || !status) {
        return;
    }

    button.addEventListener('click', async function () {
        const text = body.value.trim();

        if (!text) {
            status.textContent = @json(__('whatsapp.ai_rewrite_empty'));
            return;
        }

        button.disabled = true;
        status.textContent = @json(__('whatsapp.ai_rewrite_loading'));

        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                },
                body: JSON.stringify({
                    text: text,
                    instruction: @json(__('whatsapp.ai_rewrite_instruction'))
                })
            });

            if (!response.ok) {
                throw new Error('AI rewrite request failed');
            }

            const payload = await response.json();
            const content = payload?.data?.content;

            if (!content) {
                throw new Error('AI rewrite response is empty');
            }

            body.value = content;
            status.textContent = @json(__('whatsapp.ai_rewrite_success'));
        } catch (error) {
            status.textContent = @json(__('whatsapp.ai_rewrite_error'));
        } finally {
            button.disabled = false;
        }
    });
});
</script>
@endsection
