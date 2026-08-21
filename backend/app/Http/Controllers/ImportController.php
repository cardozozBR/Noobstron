<?php

namespace App\Http\Controllers;

use App\Enums\ImportTarget;
use App\Models\Import;
use App\Services\AuditService;
use App\Services\ImportDispatchService;
use App\Services\ImportPreviewService;
use App\Services\ImportUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ImportController extends Controller
{
    public function index()
    {
        $imports = Import::query()
            ->latest()
            ->paginate(20);

        return view(
            'imports.index',
            compact('imports')
        );
    }

    public function create()
    {
        return view(
            'imports.create',
            [
                'targets' => ImportTarget::cases(),
            ]
        );
    }

    public function store(
        Request $request,
        ImportUploadService $uploads,
        AuditService $audits
    ) {
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
            'target' => [
                'required',
                Rule::enum(
                    ImportTarget::class
                ),
            ],
            'delimiter' => [
                'required',
                Rule::in([
                    ',',
                    ';',
                ]),
            ],
        ]);

        $target = ImportTarget::from(
            $data['target']
        );

        $import = $uploads->store(
            $data['file'],
            $data['delimiter'],
            $target
        );

        $audits->log(
            'import.uploaded',
            'Importação enviada: '
                . $import->original_name
                . '.'
        );

        return redirect()
            ->route(
                'imports.preview',
                $import->id
            )
            ->with(
                'success',
                __('imports.uploaded')
            );
    }

    public function preview(
        int $id,
        ImportPreviewService $previews
    ) {
        $import = Import::query()
            ->findOrFail($id);

        if ($import->target === null) {
            abort(422);
        }

        $path = Storage::disk('local')
            ->path(
                $import->stored_path
            );

        $preview = $previews->preview(
            $path,
            $import->target,
            $import->delimiter,
            50
        );

        return view(
            'imports.preview',
            compact(
                'import',
                'preview'
            )
        );
    }

    public function dispatch(
        int $id,
        ImportDispatchService $dispatches,
        AuditService $audits
    ) {
        $import = Import::query()
            ->findOrFail($id);

        $dispatches->dispatch(
            $import
        );

        $audits->log(
            'import.dispatched',
            'Importação enviada para processamento: '
                . $import->original_name
                . '.'
        );

        return redirect()
            ->route(
                'imports.show',
                $import->id
            )
            ->with(
                'success',
                __('imports.dispatched')
            );
    }

    public function show(int $id)
    {
        $import = Import::query()
            ->with([
                'rows' => fn ($query) =>
                    $query->orderBy('line'),
            ])
            ->findOrFail($id);

        return view(
            'imports.show',
            compact('import')
        );
    }
}
