<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $tenant = app(TenantContext::class)->get();
        $user = auth()->user();

        abort_unless(
            $user && $user->tenant_id === $tenant->id,
            403
        );

        return view('profile.edit', [
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    public function update(
        Request $request,
        AuditService $audits
    ) {
        $tenant = app(TenantContext::class)->get();
        $user = auth()->user();

        abort_unless(
            $user && $user->tenant_id === $tenant->id,
            403
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(
                        fn ($query) => $query->where(
                            'tenant_id',
                            $tenant->id
                        )
                    )
                    ->ignore($user->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $changes = [];

        if ($user->name !== $data['name']) {
            $changes[] = 'nome alterado';
        }

        if ($user->email !== $data['email']) {
            $changes[] = 'e-mail alterado';
        }

        if (!empty($data['password'])) {
            $changes[] = 'senha alterada';
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $description = empty($changes)
            ? 'Perfil atualizado sem alterações nos dados.'
            : 'Perfil atualizado: '
                . implode('; ', $changes)
                . '.';

        $audits->log(
            'profile.updated',
            $description
        );

        return redirect()
            ->route('profile.edit')
            ->with(
                'success',
                'Perfil atualizado com sucesso.'
            );
    }
}