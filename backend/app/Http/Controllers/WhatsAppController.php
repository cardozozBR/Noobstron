<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppMessage;
use App\Services\WhatsAppMessageService;
use App\Services\WhatsAppQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function index(): View
    {
        return view(
            'whatsapp.index',
            [
                'messages' =>
                    WhatsAppMessage::query()
                        ->latest()
                        ->paginate(25),
            ]
        );
    }

    public function create(): View
    {
        return view(
            'whatsapp.create'
        );
    }

    public function store(
        Request $request,
        WhatsAppMessageService $messages,
        WhatsAppQueueService $queue
    ): RedirectResponse {
        $data = $request->validate([
            'phone' =>
                [
                    'required',
                    'string',
                    'max:32',
                ],

            'recipient_name' =>
                [
                    'nullable',
                    'string',
                    'max:255',
                ],

            'body' =>
                [
                    'required',
                    'string',
                ],

            'provider' =>
                [
                    'required',
                    'string',
                    'max:64',
                ],

            'action' =>
                [
                    'nullable',
                    'in:save,send',
                ],
        ]);

        $message = $messages->create([
            'phone' =>
                $data['phone'],

            'recipient_name' =>
                $data['recipient_name'] ?? null,

            'body' =>
                $data['body'],

            'provider' =>
                $data['provider'],
        ]);

        if (
            ($data['action'] ?? 'save') ===
            'send'
        ) {
            $queue->dispatch(
                $message,
                $data['provider']
            );
        }

        return redirect()
            ->route(
                'whatsapp.index'
            );
    }

    public function send(
        int $id,
        Request $request,
        WhatsAppQueueService $queue
    ): RedirectResponse {
        $data = $request->validate([
            'provider' =>
                [
                    'required',
                    'string',
                    'max:64',
                ],
        ]);

        $message = WhatsAppMessage::query()
            ->findOrFail(
                $id
            );

        $queue->dispatch(
            $message,
            $data['provider']
        );

        return redirect()
            ->route(
                'whatsapp.index'
            );
    }
}