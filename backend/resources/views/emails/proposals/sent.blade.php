<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">

    <title>
        {{ __('proposals.email_subject', ['number' => $proposal->number]) }}
    </title>
</head>

<body>
    <p>
        {{ __('proposals.email_greeting') }}
    </p>

    <p>
        {{
            __(
                'proposals.email_body',
                [
                    'number' => $proposal->number,
                ]
            )
        }}
    </p>

    @if ($proposal->valid_until)
        <p>
            <strong>
                {{ __('proposals.valid_until') }}:
            </strong>

            {{ $proposal->valid_until->format('Y-m-d') }}
        </p>
    @endif

    <p>
        <strong>
            {{ __('proposals.total') }}:
        </strong>

        {{
            app(\App\Support\TenantMoneyFormatter::class)
                ->formatMinor(
                    $proposal->total_minor,
                    $proposal->currency
                )
        }}
    </p>

    <p>
        {{ __('proposals.email_attachment_notice') }}
    </p>
</body>
</html>
