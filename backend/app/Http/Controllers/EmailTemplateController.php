<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = EmailTemplate::query()
            ->orderBy('name')
            ->get();

        return view(
            'email.templates',
            compact(
                'templates'
            )
        );
    }

    public function store(
        Request $request,
        EmailTemplateService $templates
    ): RedirectResponse {
        $data = $request->validate([
            'name' =>
                ['required', 'string', 'max:255'],

            'subject_template' =>
                ['required', 'string', 'max:255'],

            'body_template' =>
                ['required', 'string'],
        ]);

        $templates->create(
            $data
        );

        return redirect()
            ->route(
                'email.templates.index'
            )
            ->with(
                'success',
                __('email.flash.template_created')
            );
    }

    public function update(
        Request $request,
        int $id,
        EmailTemplateService $templates
    ): RedirectResponse {
        $template = EmailTemplate::query()
            ->findOrFail(
                $id
            );

        $data = $request->validate([
            'name' =>
                ['required', 'string', 'max:255'],

            'subject_template' =>
                ['required', 'string', 'max:255'],

            'body_template' =>
                ['required', 'string'],
        ]);

        $templates->update(
            $template,
            $data
        );

        return redirect()
            ->route(
                'email.templates.index'
            )
            ->with(
                'success',
                __('email.flash.template_updated')
            );
    }

    public function destroy(
        int $id
    ): RedirectResponse {
        $template = EmailTemplate::query()
            ->findOrFail(
                $id
            );

        $template->delete();

        return redirect()
            ->route(
                'email.templates.index'
            )
            ->with(
                'success',
                __('email.flash.template_deleted')
            );
    }
}