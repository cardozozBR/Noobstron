@extends('layouts.marketing')

@section('title', __('legal.' . $document . '.title') . ' — Noobstron')

@section('content')

<style>
.public-legal-page{padding:20px 0 40px}
.public-legal-page .legal-card{max-width:900px;margin:0 auto;padding:30px;border:1px solid #e5e7eb;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.05)}
.public-legal-page .section-heading{margin-bottom:28px}
.public-legal-page .legal-sections{display:grid;gap:26px;line-height:1.75}
.public-legal-page .legal-section{padding:0}
.public-legal-page .legal-section h2{margin:0 0 8px;font-size:22px;color:#111827}
.public-legal-page .legal-section p{margin:0;color:#4b5563;white-space:pre-line}
</style>

<div class="public-legal-page">
    <main>
        <section>
            <div class="shell">
                <div class="section-heading">
                    <h1>{{ __('legal.' . $document . '.title') }}</h1>
                    <p>{{ __('legal.last_updated', ['date' => '19/08/2026']) }}</p>
                </div>

                <div class="legal-card"><div class="legal-sections">
                    @foreach (__('legal.' . $document . '.sections') as $section)
                        <section class="legal-section">
                            <h2>{{ $section['title'] }}</h2>
                            <p>{{ $section['body'] }}</p>
                        </section>
                    @endforeach
                </div></div>
            </div>
        </section>
    </main>
</div>
@endsection
