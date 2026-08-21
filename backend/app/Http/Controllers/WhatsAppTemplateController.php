<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppTemplateController extends Controller
{
    public function index(): View
    {
        return view(
            'whatsapp.templates',
            [
                'templates' =>
                    WhatsAppTemplate::query()
                        ->orderBy(
                            'name'
                        )
                        ->get(),
            ]
        );
    }

    public function store(
        Request $request,
        WhatsAppTemplateService $service
    ): RedirectResponse {
        $data = $request->validate([
            'name' =>
                [
                    'required',
                    'string',
                    'max:255',
                ],

            'body_template' =>
                [
                    'required',
                    'string',
                ],

            'provider' =>
                [
                    'nullable',
                    'string',
                    'max:64',
                ],

            'provider_template_name' =>
                [
                    'nullable',
                    'string',
                    'max:255',
                ],

            'language' =>
                [
                    'nullable',
                    'string',
                    'max:16',
                ],
        ]);

        $service->create(
            $data
        );

        return redirect()
            ->route(
                'whatsapp.templates.index'
            );
    }

    public function update(
        int $id,
        Request $request,
        WhatsAppTemplateService $service
    ): RedirectResponse {
        $template = WhatsAppTemplate::query()
            ->findOrFail(
                $id
            );

        $data = $request->validate([
            'name' =>
                [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                ],

            'body_template' =>
                [
                    'sometimes',
                    'required',
                    'string',
                ],

            'active' =>
                [
                    'sometimes',
                    'boolean',
                ],
        ]);

        $service->update(
            $template,
            $data
        );

        return redirect()
            ->route(
                'whatsapp.templates.index'
            );
    }

    public function destroy(
        int $id
    ): RedirectResponse {
        WhatsAppTemplate::query()
            ->findOrFail(
                $id
            )
            ->delete();

        return redirect()
            ->route(
                'whatsapp.templates.index'
            );
    }
}