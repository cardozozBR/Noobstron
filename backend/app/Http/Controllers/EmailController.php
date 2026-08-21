<?php

namespace App\Http\Controllers;

use App\Enums\EmailMessageStatus;
use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use App\Services\EmailMessageService;
use App\Services\EmailQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function index(): View
    {
        $messages = EmailMessage::query()
            ->latest()
            ->paginate(20);

        return view(
            'email.index',
            compact(
                'messages'
            )
        );
    }

    public function create(): View
    {
        $templates = EmailTemplate::query()
            ->orderBy('name')
            ->get();

        return view(
            'email.create',
            compact(
                'templates'
            )
        );
    }

    public function store(
        Request $request,
        EmailMessageService $messages,
        EmailQueueService $queue
    ): RedirectResponse {
        $data = $request->validate([
            'to_email' =>
                ['required', 'email', 'max:255'],

            'to_name' =>
                ['nullable', 'string', 'max:255'],

            'subject' =>
                ['required', 'string', 'max:255'],

            'body' =>
                ['required', 'string'],

            'action' =>
                ['nullable', 'in:save,send'],
        ]);

        $message = $messages->create([
            'to_email' =>
                $data['to_email'],

            'to_name' =>
                $data['to_name'] ?? null,

            'subject' =>
                $data['subject'],

            'body' =>
                $data['body'],
        ]);

        if (
            ($data['action'] ?? 'save')
            === 'send'
        ) {
            $queue->dispatch(
                $message
            );

            return redirect()
                ->route(
                    'email.index'
                )
                ->with(
                    'success',
                    __('email.flash.queued')
                );
        }

        return redirect()
            ->route(
                'email.index'
            )
            ->with(
                'success',
                __('email.flash.saved')
            );
    }

    public function send(
        int $id,
        EmailQueueService $queue
    ): RedirectResponse {
        $message = EmailMessage::query()
            ->findOrFail(
                $id
            );

        if (
            $message->status !==
            EmailMessageStatus::PENDING
        ) {
            return redirect()
                ->route(
                    'email.index'
                )
                ->with(
                    'error',
                    __('email.flash.not_pending')
                );
        }

        $queue->dispatch(
            $message
        );

        return redirect()
            ->route(
                'email.index'
            )
            ->with(
                'success',
                __('email.flash.queued')
            );
    }

    public function retry(
        int $id,
        EmailQueueService $queue
    ): RedirectResponse {
        $message = EmailMessage::query()
            ->findOrFail(
                $id
            );

        if (
            $message->status !==
            EmailMessageStatus::FAILED
        ) {
            return redirect()
                ->route(
                    'email.index'
                )
                ->with(
                    'error',
                    __('email.flash.not_failed')
                );
        }

        $queue->dispatch(
            $message
        );

        return redirect()
            ->route(
                'email.index'
            )
            ->with(
                'success',
                __('email.flash.retry_queued')
            );
    }
}