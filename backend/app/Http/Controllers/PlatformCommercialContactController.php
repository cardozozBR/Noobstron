<?php

namespace App\Http\Controllers;

use App\Enums\CommercialContactStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use App\Models\CommercialContact;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformCommercialContactController extends Controller
{
    public function index(Request $request): View
{
    $status = trim(
        (string) $request->query('status', '')
    );

    $validStatuses = array_map(
        fn (CommercialContactStatus $status) =>
            $status->value,
        CommercialContactStatus::cases()
    );

    $query = CommercialContact::query()
        ->latest();

    if (
        $status !== ''
        && in_array(
            $status,
            $validStatuses,
            true
        )
    ) {
        $query->where(
            'status',
            $status
        );
    } else {
        $status = '';
    }

    return view('platform.contacts.index', [
        'contacts' => $query
            ->paginate(30)
            ->withQueryString(),

        'status' => $status,
        'statuses' => CommercialContactStatus::cases(),
    ]);
}

public function updateStatus(
    Request $request,
    CommercialContact $contact
): RedirectResponse {
    $data = $request->validate([
        'status' => [
            'required',
            Rule::enum(
                CommercialContactStatus::class
            ),
        ],
    ]);

    $contact->forceFill([
        'status' => $data['status'],
    ])->save();

    return redirect()
        ->route(
            'platform.contacts.index',
            [
                'status' =>
                    $request->query('status'),
            ]
        )
        ->with(
            'success',
            __('platform.contacts.status_updated')
        );
}

}
