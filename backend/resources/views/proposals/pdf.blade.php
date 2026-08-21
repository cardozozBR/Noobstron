<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('proposals.proposal_document') }}
        {{ $proposal->number }}
    </title>

    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #222;
        }

        h1,
        h2,
        p {
            margin-top: 0;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .muted {
            color: #666;
        }

        .header {
            margin-bottom: 24px;
        }

        .meta {
            width: 100%;
            margin-bottom: 22px;
            border-collapse: collapse;
        }

        .meta td {
            width: 50%;
            vertical-align: top;
            padding: 3px 8px 3px 0;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items th,
        .items td {
            border-bottom: 1px solid #ddd;
            padding: 7px 5px;
            text-align: left;
            vertical-align: top;
        }

        .items th {
            font-size: 10px;
            background: #f3f3f3;
        }

        .right {
            text-align: right !important;
        }

        .totals {
            width: 45%;
            margin-left: 55%;
            margin-top: 18px;
            border-collapse: collapse;
        }

        .totals td {
            padding: 4px;
        }

        .grand-total {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px solid #222;
        }

        .notes {
            margin-top: 24px;
        }
    </style>
</head>

<body>
    @php
        $money = app(
            \App\Support\TenantMoneyFormatter::class
        );
    @endphp

    <div class="header">
        <h1>{{ __('proposals.proposal_document') }}</h1>

        <div class="muted">
            {{ $proposal->number }}
        </div>
    </div>

    <table class="meta">
        <tr>
            <td>
                <strong>{{ __('proposals.issued_by') }}:</strong><br>
                {{ $proposal->tenant->name }}
            </td>

            <td>
                <strong>{{ __('proposals.customer') }}:</strong><br>
                {{ $proposal->customer?->name ?? '—' }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>{{ __('proposals.status') }}:</strong><br>
                {{ __('proposals.' . $proposal->status->value) }}
            </td>

            <td>
                <strong>{{ __('proposals.valid_until') }}:</strong><br>
                {{
                    $proposal->valid_until
                        ? $proposal->valid_until->format('Y-m-d')
                        : '—'
                }}
            </td>
        </tr>

        @if ($proposal->opportunity)
            <tr>
                <td colspan="2">
                    <strong>{{ __('proposals.opportunity') }}:</strong><br>
                    {{ $proposal->opportunity->name }}
                </td>
            </tr>
        @endif
    </table>

    <h2>{{ __('proposals.items') }}</h2>

    <table class="items">
        <thead>
            <tr>
                <th>{{ __('proposals.description_label') }}</th>
                <th>{{ __('proposals.item_code') }}</th>
                <th class="right">{{ __('proposals.quantity') }}</th>
                <th class="right">{{ __('proposals.unit_price') }}</th>
                <th class="right">{{ __('proposals.discount') }}</th>
                <th class="right">{{ __('proposals.taxes') }}</th>
                <th class="right">{{ __('proposals.total') }}</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($proposal->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->code ?? '—' }}</td>

                    <td class="right">
                        {{
                            rtrim(
                                rtrim(
                                    number_format(
                                        (float) $item->quantity,
                                        4,
                                        '.',
                                        ''
                                    ),
                                    '0'
                                ),
                                '.'
                            )
                        }}
                    </td>

                    <td class="right">
                        {{
                            $money->formatMinor(
                                $item->unit_price_minor,
                                $proposal->currency
                            )
                        }}
                    </td>

                    <td class="right">
                        {{
                            $money->formatMinor(
                                $item->discount_minor,
                                $proposal->currency
                            )
                        }}
                    </td>

                    <td class="right">
                        {{
                            $money->formatMinor(
                                $item->tax_minor,
                                $proposal->currency
                            )
                        }}
                    </td>

                    <td class="right">
                        {{
                            $money->formatMinor(
                                $item->total_minor,
                                $proposal->currency
                            )
                        }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('proposals.subtotal') }}</td>

            <td class="right">
                {{
                    $money->formatMinor(
                        $proposal->subtotal_minor,
                        $proposal->currency
                    )
                }}
            </td>
        </tr>

        <tr>
            <td>{{ __('proposals.discount') }}</td>

            <td class="right">
                {{
                    $money->formatMinor(
                        $proposal->discount_minor,
                        $proposal->currency
                    )
                }}
            </td>
        </tr>

        <tr>
            <td>{{ __('proposals.taxes') }}</td>

            <td class="right">
                {{
                    $money->formatMinor(
                        $proposal->tax_minor,
                        $proposal->currency
                    )
                }}
            </td>
        </tr>

        <tr class="grand-total">
            <td>{{ __('proposals.total') }}</td>

            <td class="right">
                {{
                    $money->formatMinor(
                        $proposal->total_minor,
                        $proposal->currency
                    )
                }}
            </td>
        </tr>
    </table>

    @if ($proposal->notes)
        <div class="notes">
            <h2>{{ __('proposals.notes') }}</h2>
            <p>{{ $proposal->notes }}</p>
        </div>
    @endif
</body>
</html>
