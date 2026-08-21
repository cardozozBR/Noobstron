<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

use App\Enums\ConversationChannel;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ConversationHistoryService;
use App\Services\ConversationInboxService;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(
        Request $request,
        ConversationInboxService $inbox
    ): JsonResponse|View {
        $filters = $request->only([
            'search',
            'channel',
            'status',
            'responsible_user_id',
            'lead_id',
            'customer_id',
        ]);

        $perPage = $request->integer(
            'per_page',
            20
        );

        $conversations = $inbox->paginate(
            $filters,
            $perPage
        );

        if ($request->expectsJson()) {
            return response()->json(
                $conversations
            );
        }

        return view(
            'inbox.index',
            [
                'conversations' =>
                    $conversations,

                'channels' =>
                    ConversationChannel::cases(),

                'statuses' =>
                    ConversationStatus::cases(),
            ]
        );
    }
    public function show(
        Request $request,
        int $id,
        ConversationHistoryService $history
    ): JsonResponse|View {
        $conversation = Conversation::query()
            ->with([
                'responsible:id,name,email',
                'lead:id,name',
                'customer:id,name',
            ])
            ->findOrFail(
                $id
            );

        $conversationHistory = $history
            ->history(
                $conversation
            )
            ->values();

        if ($request->expectsJson()) {
            return response()->json([
                'conversation' =>
                    $conversation,

                'history' =>
                    $conversationHistory,
            ]);
        }

        return view(
            'inbox.show',
            [
                'conversation' =>
                    $conversation,

                'history' =>
                    $conversationHistory,

                'statuses' =>
                    ConversationStatus::cases(),
            ]
        );
    }
    public function assign(
        Request $request,
        int $id,
        ConversationService $service,
        AuditService $audits
    ): Response {
        $data = $request->validate([
            'responsible_user_id' => [
                'nullable',
                'integer',
            ],
        ]);

        $conversation = Conversation::query()
            ->findOrFail(
                $id
            );

        $user = null;

        if (
            ($data['responsible_user_id'] ?? null)
            !== null
        ) {
            $user = User::query()
                ->findOrFail(
                    (int) $data[
                        'responsible_user_id'
                    ]
                );
        }

        $conversation = $service->assign(
            $conversation,
            $user
        );

        $audits->log(
            'inbox.conversation.assigned',
            'Responsável da conversa atualizado.'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'conversation' =>
                    $conversation,
            ]);
        }

        return redirect()
            ->route(
                'inbox.show',
                $conversation->id
            )
            ->with(
                'success',
                __('inbox.flash.assigned')
            );
    }

    public function status(
        Request $request,
        int $id,
        ConversationService $service,
        AuditService $audits
    ): Response {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::enum(
                    ConversationStatus::class
                ),
            ],
        ]);

        $conversation = Conversation::query()
            ->findOrFail(
                $id
            );

        $target = ConversationStatus::from(
            $data['status']
        );

        if (
            $conversation->status !==
            $target
        ) {
            $conversation = match ($target) {
                ConversationStatus::OPEN =>
                    $service->reopen(
                        $conversation
                    ),

                ConversationStatus::PENDING =>
                    $service->markPending(
                        $conversation
                    ),

                ConversationStatus::CLOSED =>
                    $service->close(
                        $conversation
                    ),
            };
        }

        $audits->log(
            'inbox.conversation.status_updated',
            'Status da conversa atualizado para '
                . $conversation->status->value
                . '.'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'conversation' =>
                    $conversation,
            ]);
        }

        return redirect()
            ->route(
                'inbox.show',
                $conversation->id
            )
            ->with(
                'success',
                __('inbox.flash.status_updated')
            );
    }
}