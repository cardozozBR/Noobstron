<?php

namespace App\Http\Controllers;

use App\Enums\CatalogItemType;
use App\Models\CatalogItem;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = CatalogItem::query()
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'code',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->input('type')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->input('status') === 'active'
            );
        }

        return view('catalog.index', [
            'items' => $query
                ->paginate(20)
                ->withQueryString(),
            'types' => CatalogItemType::cases(),
        ]);
    }

    public function create()
    {
        return view('catalog.create', [
            'types' => CatalogItemType::cases(),
        ]);
    }

    public function store(
        Request $request,
        CatalogService $service
    ) {
        $service->create(
            $this->validatedData(
                $request
            )
        );

        return redirect()
            ->route('catalog.index')
            ->with(
                'success',
                __('catalog.created')
            );
    }

    public function edit(int $id)
    {
        return view('catalog.edit', [
            'item' => CatalogItem::query()
                ->findOrFail($id),
            'types' => CatalogItemType::cases(),
        ]);
    }

    public function update(
        Request $request,
        int $id,
        CatalogService $service
    ) {
        $item = CatalogItem::query()
            ->findOrFail($id);

        $service->update(
            $item,
            $this->validatedData(
                $request
            )
        );

        return redirect()
            ->route('catalog.index')
            ->with(
                'success',
                __('catalog.updated')
            );
    }

    public function destroy(
        int $id
    ) {
        $item = CatalogItem::query()
            ->findOrFail($id);

        $item->delete();

        return redirect()
            ->route('catalog.index')
            ->with(
                'success',
                __('catalog.deleted')
            );
    }

    private function validatedData(
        Request $request
    ): array {
        return $request->validate([
            'type' => [
                'required',
                Rule::enum(
                    CatalogItemType::class
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'nullable',
                'string',
                'max:100',
            ],
            'price_minor' => [
                'required',
                'integer',
                'min:0',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }
}
