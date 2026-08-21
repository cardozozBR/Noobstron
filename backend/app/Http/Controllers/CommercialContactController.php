<?php

namespace App\Http\Controllers;

use App\Models\CommercialContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CommercialContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contact = CommercialContact::query()->create([
            ...$data,
            'locale' => app()->getLocale(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr(
                (string) $request->userAgent(),
                0,
                1000
            ),
            'status' => 'new',
        ]);

        $recipient = trim(
            (string) config('marketing.contact_recipient', '')
        );

        if ($recipient !== '') {
            try {
                Mail::raw(
                    $this->mailBody($contact),
                    function ($message) use ($contact, $recipient): void {
                        $message
                            ->to($recipient)
                            ->replyTo($contact->email, $contact->name)
                            ->subject(
                                'Novo contato comercial — '
                                . ($contact->company ?: $contact->name)
                            );
                    }
                );
            } catch (\Throwable $exception) {
                Log::warning(
                    'Commercial contact email delivery failed.',
                    [
                        'commercial_contact_id' => $contact->id,
                        'exception' => $exception::class,
                    ]
                );
            }
        }

        return redirect()
            ->to(rtrim(route('marketing.home'), '/') . '/#contato')
            ->with('contact_status', 'sent');
    }

    private function mailBody(CommercialContact $contact): string
    {
        return implode("\n", [
            'Novo contato comercial',
            '',
            'Nome: ' . $contact->name,
            'E-mail: ' . $contact->email,
            'Empresa: ' . ($contact->company ?: '-'),
            'Idioma: ' . ($contact->locale ?: '-'),
            '',
            'Mensagem:',
            $contact->message,
        ]);
    }
}
